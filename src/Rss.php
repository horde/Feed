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

use DOMElement;

/**
 * RSS channel class.
 *
 * The Rss class is a concrete subclass of FeedBase meant for representing
 * RSS channels. It does not add any methods to its parent, just provides
 * a classname to check against with the instanceof operator, and expects
 * to be handling RSS-formatted data instead of Atom.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class Rss extends FeedBase
{
    protected string $listItemClassName = RssEntry::class;
    protected string $defaultNamespace = 'rss';
    protected string $emptyXml = '<?xml version="1.0" encoding="utf-8"?><rss version="2.0"><channel></channel></rss>';

    /**
     * Cache the individual feed elements so they don't need to be searched for
     * on every operation.
     *
     * @return array<int, DOMElement> Array of item elements
     */
    public function buildListItemCache(): array
    {
        $items = [];
        foreach ($this->element->childNodes as $child) {
            if ($child->localName === 'item') {
                $items[] = $child;
            }
        }

        // Brute-force search for <item> elements if we haven't found any so
        // far.
        if (!count($items)) {
            foreach ($this->element->ownerDocument->getElementsByTagName('item') as $child) {
                $items[] = $child;
            }
        }

        return $items;
    }
}
