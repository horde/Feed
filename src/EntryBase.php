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
use Horde\Xml\Element\Element;
use Psr\Http\Client\ClientInterface;

/**
 * EntryBase represents a single entry in an Atom or RSS feed.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
abstract class EntryBase extends Element
{
    protected ?ClientInterface $httpClient = null;
    protected string $emptyXml = '';

    /**
     * Constructor.
     *
     * Handle null or array values for $element by initializing
     * with $this->emptyXml, and importing the array with
     * Element::fromArray() if necessary.
     *
     * @param mixed $element DOMElement, Element, XML string, array, or null
     * @param ClientInterface|null $httpClient HTTP client for publishing operations
     */
    public function __construct(
        mixed $element = null,
        ?ClientInterface $httpClient = null
    ) {
        $this->httpClient = $httpClient ?? new Curl(
            new ResponseFactory(),
            new StreamFactory(),
            new Options()
        );

        // If we've been passed an array, we'll store it for importing
        // after initializing with the default "empty" feed XML.
        $importArray = null;
        if ($element === null) {
            $element = $this->emptyXml;
        } elseif (is_array($element)) {
            $importArray = $element;
            $element = $this->emptyXml;
        }

        parent::__construct($element);

        if ($importArray !== null) {
            $this->fromArray($importArray);
        }
    }
}
