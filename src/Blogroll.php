<?php

declare(strict_types=1);

/**
 * Copyright 2008-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed;

use DOMElement;

/**
 * Blogroll feed list class.
 *
 * This is not a generic OPML implementation, but one focused on lists of
 * feeds, i.e. blogrolls. See http://en.wikipedia.org/wiki/OPML for more
 * information on OPML.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class Blogroll extends FeedBase
{
    protected string $listItemClassName = BlogrollEntry::class;
    protected string $defaultNamespace = '';
    protected string $emptyXml = '<?xml version="1.0" encoding="utf-8"?><opml version="1.1"></opml>';

    /**
     * Cache outline elements so they don't need to be searched for on every
     * operation.
     *
     * @return array<int, DOMElement>
     */
    public function buildListItemCache(): array
    {
        $entries = [];
        foreach ($this->element->getElementsByTagName('outline') as $child) {
            if ($child->attributes->getNamedItem('xmlUrl')) {
                $entries[] = $child;
            }
        }

        return $entries;
    }

    /**
     * Get the body element.
     *
     * @return self
     */
    public function getBody(): self
    {
        return $this;
    }

    /**
     * Get the outline element.
     *
     * @return self
     */
    public function getOutline(): self
    {
        return $this;
    }

    /**
     * Get the feed title.
     *
     * @return mixed The title value
     */
    public function getTitle(): mixed
    {
        // Access via DOM to avoid Element chaining issues
        $headElements = $this->element->getElementsByTagName('head');
        if ($headElements->length > 0) {
            $titleElements = $headElements->item(0)->getElementsByTagName('title');
            if ($titleElements->length > 0) {
                return $titleElements->item(0)->nodeValue;
            }
        }
        return null;
    }
}
