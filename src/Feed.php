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
use Horde\Http\Client\Curl;
use Horde\Http\Client\Options;
use Horde\Http\Exception as HttpException;
use Horde\Http\RequestFactory;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use LibXMLError;
use Psr\Http\Client\ClientInterface;

/**
 * Static class representing a feed client.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class Feed
{
    /**
     * Create a Feed object based on a DOMDocument.
     *
     * @param DOMDocument $doc The DOMDocument object to import
     * @param string|null $uri The full URI of the feed, or null if unknown
     * @param ClientInterface|null $httpClient HTTP client for entry operations
     *
     * @return Atom|Rss|Blogroll The feed object imported from $doc
     * @throws Exception If the feed format is invalid or unsupported
     */
    public static function create(
        DOMDocument $doc,
        ?string $uri = null,
        ?ClientInterface $httpClient = null
    ): Atom|Rss|Blogroll {
        // Try to find the base feed element or a single <entry> of an
        // Atom feed.
        if ($feed = $doc->getElementsByTagName('feed')->item(0)) {
            // Return an Atom feed.
            return new Atom($feed, $uri, $httpClient);
        } elseif ($entry = $doc->getElementsByTagName('entry')->item(0)) {
            // Return an Atom single-entry feed.
            $feeddoc = new DOMDocument(
                $doc->version ?? '1.0',
                $doc->encoding ?? 'UTF-8'
            );
            $feed = $feeddoc->appendChild($feeddoc->createElement('feed'));
            $feed->appendChild($feeddoc->importNode($entry, true));

            return new Atom($feed, $uri, $httpClient);
        }

        // Try to find the base feed element of an RSS feed.
        if ($channel = $doc->getElementsByTagName('channel')->item(0)) {
            // Return an RSS feed.
            return new Rss($channel, $uri, $httpClient);
        }

        // Try to find an outline element of an OPML blogroll.
        if ($outline = $doc->getElementsByTagName('outline')->item(0)) {
            // Return a blogroll feed.
            return new Blogroll($doc->documentElement, $uri, $httpClient);
        }

        // $doc does not appear to be a valid feed of the supported types.
        throw new Exception(
            'Invalid or unsupported feed format: '
            . substr($doc->saveXML(), 0, 80) . '...'
        );
    }

    /**
     * Reads a feed represented by $string.
     *
     * @param string $string The XML content of the feed
     * @param string|null $uri The feed's URI location, if known
     * @param ClientInterface|null $httpClient HTTP client for entry operations
     *
     * @return Atom|Rss|Blogroll The feed object
     * @throws Exception If the feed cannot be parsed
     */
    public static function read(
        string $string,
        ?string $uri = null,
        ?ClientInterface $httpClient = null
    ): Atom|Rss|Blogroll {
        // Load the feed as a DOMDocument object.
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->recover = true;
        $loaded = $doc->loadXML($string);
        if (!$loaded) {
            $loaded = $doc->loadHTML($string);
            if (!$loaded) {
                self::exception('DOMDocument cannot parse XML', libxml_get_last_error());
            }
        }

        return self::create($doc, $uri, $httpClient);
    }

    /**
     * Read a feed located at $uri.
     *
     * @param string $uri The URI to fetch the feed from
     * @param ClientInterface|null $httpClient The HTTP client to use
     *
     * @return Atom|Rss|Blogroll The feed object
     * @throws Exception If the feed cannot be fetched or parsed
     */
    public static function readUri(
        string $uri,
        ?ClientInterface $httpClient = null
    ): Atom|Rss|Blogroll {
        $httpClient ??= new Curl(
            new ResponseFactory(),
            new StreamFactory(),
            new Options()
        );

        $requestFactory = new RequestFactory();
        $request = $requestFactory->createRequest('GET', $uri);

        try {
            $response = $httpClient->sendRequest($request);
        } catch (HttpException $e) {
            throw new Exception('Error reading feed: ' . $e->getMessage(), 0, $e);
        }

        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                'Unable to read feed, got response code ' . $response->getStatusCode()
            );
        }

        $feed = $response->getBody()->getContents();
        return self::read($feed, $uri, $httpClient);
    }

    /**
     * Read a feed from $filename.
     *
     * @param string $filename The location of the feed file on an accessible
     *                         filesystem or through an available stream wrapper
     *
     * @return Atom|Rss|Blogroll The feed object
     * @throws Exception If the file cannot be read or parsed
     */
    public static function readFile(string $filename): Atom|Rss|Blogroll
    {
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        $doc->recover = true;
        $contents = file_get_contents($filename);
        $loaded = $doc->loadXML($contents);
        if (!$loaded) {
            $loaded = $doc->loadHTML($contents);
            if (!$loaded) {
                self::exception('File could not be read or parsed', libxml_get_last_error());
            }
        }

        return self::create($doc);
    }

    /**
     * Builds an exception message from a libXMLError object.
     *
     * @param string $msg Error message
     * @param LibXMLError|false|null $error Error object from libxml
     *
     * @throws Exception Always throws
     */
    protected static function exception(string $msg, LibXMLError|false|null $error): never
    {
        if ($error) {
            $msg .= ': ' . $error->message;
            if ($error->file) {
                $msg .= sprintf(
                    ' in file %s, line %d, column %d',
                    $error->file,
                    $error->line,
                    $error->column
                );
            }
        }
        throw new Exception($msg);
    }
}
