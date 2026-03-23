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

/**
 * Concrete class for working with RSS items.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2005-2007 Zend Technologies USA Inc.
 * @copyright 2007-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class RssEntry extends EntryBase
{
    protected string $emptyXml = '<item/>';

    /**
     * Return encoded content if it's present.
     *
     * @return mixed Content value or array
     */
    public function getContent(): mixed
    {
        if (isset($this->children['content:encoded'])) {
            return $this->children['content:encoded'];
        } elseif (isset($this->children['encoded'])) {
            return $this->children['encoded'];
        }
        return $this->children['content'] ?? [];
    }
}
