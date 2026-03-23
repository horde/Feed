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

use Horde\Xml\Element\Element;
use DOMElement;

/**
 * Atom feed class.
 *
 * The Atom class is a concrete subclass of FeedBase, tailored for
 * representing an Atom feed. It shares all of the same methods with its
 * parent. The distinction is made in the format of data that Atom expects,
 * and as a further pointer for users as to what kind of feed object they
 * have been passed.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class Atom extends FeedBase
{
    protected string $listItemClassName = AtomEntry::class;
    protected string $defaultNamespace = 'atom';
    protected string $emptyXml = '<?xml version="1.0" encoding="utf-8"?><feed xmlns="http://www.w3.org/2005/Atom"></feed>';

    /**
     * Cache the individual feed elements so they don't need to be
     * searched for on every operation.
     *
     * @return array<int, DOMElement> Array of entry elements
     */
    public function buildListItemCache(): array
    {
        $entries = [];
        foreach ($this->element->childNodes as $child) {
            if ($child->localName === 'entry') {
                $entries[] = $child;
            }
        }

        return $entries;
    }

    /**
     * Easy access to <link> tags keyed by "rel" attributes.
     *
     * If $feed->link() is called with no arguments, we will attempt to return
     * the value of the <link> tag(s) like all other method-syntax attribute
     * access. If an argument is passed to link(), however, then we will return
     * the "href" value of the first <link> tag that has a "rel" attribute
     * matching $rel:
     *
     * $feed->link(): returns the value of the link tag.
     * $feed->link('self'): returns the href from the first <link rel="self"> in the feed.
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
