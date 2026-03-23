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

use Horde\Http\Client\Curl;
use Horde\Http\Client\Options;
use Horde\Http\ResponseFactory;
use Horde\Http\StreamFactory;
use Horde\Xml\Element\ElementList;
use Horde\Xml\Element\Exception as XmlException;
use Psr\Http\Client\ClientInterface;
use DOMElement;

/**
 * FeedBase is an abstract class representing feeds.
 *
 * FeedBase implements two core PHP interfaces: ArrayAccess and Iterator.
 * In both cases the collection being treated as an array is considered to be
 * the entry collection, such that iterating over the feed takes you through
 * each of the feed's entries.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
abstract class FeedBase extends ElementList implements FeedInterface
{
    protected ?string $uri = null;
    protected ?ClientInterface $httpClient = null;
    protected string $emptyXml = '';

    /**
     * Feed constructor.
     *
     * The FeedBase constructor takes the URI of a feed or a feed represented
     * as a string and loads it as XML.
     *
     * @param mixed $xml The feed as a string, a DOMElement, or null
     * @param string|null $uri The full URI of the feed, or null if unknown
     * @param ClientInterface|null $httpClient HTTP client for entry operations
     *
     * @throws Exception If loading the feed failed
     */
    public function __construct(
        mixed $xml = null,
        ?string $uri = null,
        ?ClientInterface $httpClient = null
    ) {
        $this->uri = $uri;
        $this->httpClient = $httpClient ?? new Curl(
            new ResponseFactory(),
            new StreamFactory(),
            new Options()
        );

        try {
            parent::__construct($xml);
        } catch (XmlException $e) {
            throw new Exception('Unable to load feed: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get the feed's URI.
     *
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * Get the HTTP client.
     *
     * @return ClientInterface|null
     */
    public function getHttpClient(): ?ClientInterface
    {
        return $this->httpClient;
    }

    /**
     * Handle null or array values for $this->element by initializing
     * with $this->emptyXml, and importing the array with
     * Element::fromArray() if necessary.
     */
    public function __wakeup(): void
    {
        // If we've been passed an array, we'll store it for importing
        // after initializing with the default "empty" feed XML.
        $importArray = null;
        if (!isset($this->element)) {
            $this->element = $this->emptyXml;
        } elseif (is_array($this->element)) {
            $importArray = $this->element;
            $this->element = $this->emptyXml;
        }

        parent::__wakeup();

        if ($importArray !== null) {
            $this->fromArray($importArray);
        }
    }

    /**
     * Required by the Iterator interface.
     *
     * @return mixed The current entry with HTTP client injected
     */
    public function current(): mixed
    {
        return new ($this->listItemClassName)(
            $this->listItems[$this->listItemIndex],
            $this->httpClient
        );
    }

    /**
     * Build the cache of list items for iteration.
     *
     * Must be implemented by subclasses to return array of DOMElements.
     *
     * @return array<int, DOMElement>
     */
    abstract public function buildListItemCache(): array;
}
