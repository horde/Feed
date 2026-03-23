<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed;

use Countable;
use Horde\Xml\Element\ElementListInterface;
use Iterator;
use Psr\Http\Client\ClientInterface;
use DOMElement;

/**
 * Interface for Feed objects.
 *
 * @category Horde
 * @license  http://www.horde.org/licenses/bsd BSD
 * @package  Feed
 */
interface FeedInterface extends ElementListInterface, Countable, Iterator
{
    /**
     * Get the feed's URI.
     *
     * @return string|null
     */
    public function getUri(): ?string;

    /**
     * Get the HTTP client.
     *
     * @return ClientInterface|null
     */
    public function getHttpClient(): ?ClientInterface;

    /**
     * Build the cache of list items for iteration.
     *
     * @return array<int, DOMElement> Array of DOM elements to iterate over
     */
    public function buildListItemCache(): array;
}
