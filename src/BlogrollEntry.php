<?php

declare(strict_types=1);

/**
 * Copyright 2008-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed;

/**
 * Concrete class for working with Blogroll elements.
 *
 * @author    Chuck Hagenbuch <chuck@horde.org>
 * @category  Horde
 * @copyright 2008-2026 Horde LLC
 * @license   http://www.horde.org/licenses/bsd BSD
 * @package   Feed
 */
class BlogrollEntry extends EntryBase
{
    protected string $emptyXml = '<outline xmlUrl=""/>';

    /**
     * Get a Feed object for the feed described by this outline element.
     *
     * @return Atom|Rss|Blogroll The feed object
     * @throws Exception If no XML URL is present
     */
    public function getFeed(): Atom|Rss|Blogroll
    {
        if (!$this['xmlUrl']) {
            throw new Exception('No XML URL in <outline/> element');
        }
        return Feed::readUri($this['xmlUrl'], $this->httpClient);
    }

    /**
     * Add child elements and attributes to this element from a simple key =>
     * value hash. Because feed list outline elements only use attributes, this
     * overrides Element::fromArray to set attributes whether the
     * #Attribute syntax is used or not.
     *
     * @param array $array Hash to import into this element
     *
     * @return void
     */
    public function fromArray(array $array): void
    {
        foreach ($array as $key => $value) {
            $attribute = $key;
            if (str_starts_with($attribute, '#')) {
                $attribute = substr($attribute, 1);
            }
            $this[$attribute] = $value;
        }
    }

    /**
     * Always use attributes instead of child nodes.
     *
     * @param string $var The property to access
     * @return mixed Attribute value
     */
    public function __get(string $var): mixed
    {
        // Allow access to the httpClient property
        if ($var === 'httpClient') {
            return $this->httpClient;
        }
        return $this->offsetGet($var);
    }

    /**
     * Always use attributes instead of child nodes.
     *
     * @param string $var The property to change
     * @param mixed $val The property's new value
     * @return void
     */
    public function __set(string $var, mixed $val): void
    {
        $this->offsetSet($var, $val);
    }
}
