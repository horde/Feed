<?php

declare(strict_types=1);

/**
 * Portions Copyright 2005-2007 Zend Technologies USA Inc. (http://www.zend.com)
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed;

use DOMDocument;
use Horde\Http\RequestFactory;
use Horde\Http\StreamFactory;
use Horde\Xml\Element\Element;

/**
 * Concrete class for working with Atom entries.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class AtomEntry extends EntryBase
{
    protected string $emptyXml = '<atom:entry/>';
    protected string $entryElementName = 'entry';
    protected ?string $lastEtag = null;

    /**
     * Get the last ETag received from the server.
     *
     * @return string|null The ETag value or null if none stored
     */
    public function getEtag(): ?string
    {
        return $this->lastEtag;
    }

    /**
     * Delete an atom entry.
     *
     * Delete tries to delete this entry from its feed. If the entry
     * does not contain a link rel="edit", we throw an error (either
     * the entry does not yet exist or this is not an editable
     * feed). If we have a link rel="edit", we do the empty-body
     * HTTP DELETE to that URI and check for a response of 2xx.
     * Usually the response would be 204 No Content, but the Atom
     * Publishing Protocol permits it to be 200 OK.
     *
     * @return bool True on successful deletion
     * @throws Exception If an error occurs
     */
    public function delete(): bool
    {
        // Look for link rel="edit" in the entry object.
        $deleteUri = $this->link('edit');
        if (!$deleteUri) {
            throw new Exception('Cannot delete entry; no link rel="edit" is present.');
        }

        $requestFactory = new RequestFactory();

        // DELETE with redirect following
        do {
            $request = $requestFactory->createRequest('DELETE', $deleteUri);
            $response = $this->httpClient->sendRequest($request);

            $statusCode = $response->getStatusCode();
            $class = (int) ($statusCode / 100);

            switch ($class) {
                // Success
                case 2:
                    return true;

                    // Redirect
                case 3:
                    $deleteUri = $response->getHeaderLine('Location');
                    continue 2;

                    // Error
                default:
                    throw new Exception(
                        'Expected response code 2xx, got ' . $statusCode
                    );
            }
        } while (true);
    }

    /**
     * Save a new or updated Atom entry.
     *
     * Save is used to either create new entries or to save changes to existing
     * ones. If we have a link rel="edit", we are changing an existing entry. In
     * this case we re-serialize the entry and PUT it to the edit URI, checking
     * for a 200 OK result.
     *
     * For posting new entries, you must specify the $postUri parameter to
     * save() to tell the object where to post itself.  We use $postUri and POST
     * the serialized entry there, checking for a 201 Created response. If the
     * insert is successful, we then parse the response from the POST to get any
     * values that the server has generated: an id, an updated time, and its new
     * link rel="edit".
     *
     * @param string|null $postUri Location to POST for creating new entries
     * @param array $headers Additional headers to transmit while posting
     * @param string|null $etag ETag value for If-Match header (updates only)
     * @param string|null $slug Slug value for URI suggestion (creates only)
     *
     * @return void
     * @throws Exception If an error occurs
     */
    public function save(
        ?string $postUri = null,
        array $headers = [],
        ?string $etag = null,
        ?string $slug = null
    ): void {
        $isCreate = empty($this->id());
        $headers = $this->buildSaveHeaders($headers, $etag, $slug, $isCreate);

        $requestFactory = new RequestFactory();
        $streamFactory = new StreamFactory();
        $xml = $this->saveXml();

        if ($this->id()) {
            // If id is set, look for link rel="edit" in the
            // entry object and PUT.
            $editUri = $this->link('edit');
            if (!$editUri) {
                throw new Exception('Cannot edit entry; no link rel="edit" is present.');
            }

            $request = $requestFactory->createRequest('PUT', $editUri)
                ->withBody($streamFactory->createStream($xml));

            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $response = $this->httpClient->sendRequest($request);
            $this->validateResponseStatus($response->getStatusCode(), 200);
        } else {
            if ($postUri === null) {
                throw new Exception('PostURI must be specified to save new entries.');
            }

            $request = $requestFactory->createRequest('POST', $postUri)
                ->withBody($streamFactory->createStream($xml));

            foreach ($headers as $name => $value) {
                $request = $request->withHeader($name, $value);
            }

            $response = $this->httpClient->sendRequest($request);
            $this->validateResponseStatus($response->getStatusCode(), 201);
        }

        // Update internal properties using the response body.
        $stream = $response->getBody();
        $stream->rewind();  // Ensure we can read from start
        $body = $stream->getContents();

        // Store ETag for future operations
        $etag = $response->getHeaderLine('ETag');
        if ($etag) {
            $this->lastEtag = $etag;
        }

        if (empty($body)) {
            // Server returned empty body, nothing to update
            return;
        }

        $newEntry = new DOMDocument();
        $parsed = @$newEntry->loadXML($body);
        if (!$parsed) {
            $lastError = error_get_last();
            throw new Exception(
                'DOMDocument cannot parse XML: ' . ($lastError['message'] ?? 'Unknown error')
            );
        }

        $newEntry = $newEntry->getElementsByTagName($this->entryElementName)->item(0);
        if (!$newEntry) {
            throw new Exception(
                'No root <feed> element found in server response:' . "\n\n" . $body
            );
        }

        if ($this->element->parentNode) {
            $oldElement = $this->element;
            $this->element = $oldElement->ownerDocument->importNode($newEntry, true);
            $oldElement->parentNode->replaceChild($this->element, $oldElement);
        } else {
            $this->element = $newEntry;
        }

        $this->expireCachedChildren();
    }

    /**
     * Build headers for save operations.
     *
     * @param array $userHeaders User-provided headers
     * @param string|null $etag ETag for If-Match header (updates only)
     * @param string|null $slug Slug for URI suggestion (creates only)
     * @param bool $isCreate True if creating new entry, false if updating
     *
     * @return array Merged headers array
     */
    private function buildSaveHeaders(
        array $userHeaders,
        ?string $etag,
        ?string $slug,
        bool $isCreate
    ): array {
        $headers = ['Content-Type' => 'application/atom+xml;type=entry'];

        // Add If-Match for updates with ETag (optimistic locking)
        if ($etag !== null && !$isCreate) {
            $headers['If-Match'] = $etag;
        }

        // Add Slug for creates (URI suggestion)
        if ($slug !== null && $isCreate) {
            $headers['Slug'] = $slug;
        }

        return array_merge($headers, $userHeaders);
    }

    /**
     * Validate that a response status code is in the expected class.
     *
     * @param int $statusCode Actual HTTP status code
     * @param int $expectedClass Expected status class (e.g., 200 for 2xx)
     *
     * @throws Exception If status code is not in expected class
     */
    private function validateResponseStatus(int $statusCode, int $expectedClass): void
    {
        $actualClass = (int) ($statusCode / 100);
        $expectedClassNum = (int) ($expectedClass / 100);

        if ($actualClass !== $expectedClassNum) {
            throw new Exception(
                sprintf(
                    'Expected %dxx response code, got %d',
                    $expectedClassNum,
                    $statusCode
                )
            );
        }
    }

    /**
     * Easy access to <link> tags keyed by "rel" attributes.
     *
     * If $elt->link() is called with no arguments, we will attempt to
     * return the value of the <link> tag(s) like all other
     * method-syntax attribute access. If an argument is passed to
     * link(), however, then we will return the "href" value of the
     * first <link> tag that has a "rel" attribute matching $rel:
     *
     * $elt->link(): returns the value of the link tag.
     * $elt->link('self'): returns the href from the first <link rel="self"> in the entry.
     *
     * @param string|null $rel The "rel" attribute to look for
     * @return mixed Link value or href attribute
     */
    public function link(?string $rel = null): mixed
    {
        if ($rel === null) {
            return parent::__call('link', []);
        }

        // Index link tags by their "rel" attribute.
        $links = parent::__get('link');
        if (!is_array($links)) {
            if ($links instanceof Element) {
                $links = [$links];
            } else {
                return $links;
            }
        }

        foreach ($links as $link) {
            // Per Atom spec, missing rel defaults to "alternate"
            $linkRel = empty($link['rel']) ? 'alternate' : $link['rel'];
            if ($rel === $linkRel) {
                return $link['href'];
            }
        }

        return null;
    }
}
