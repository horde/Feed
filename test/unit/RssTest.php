<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\Feed;
use Horde\Feed\Rss;
use Horde\Feed\RssEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(Rss::class)]
class RssTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    public function testRssFeedIteration(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $this->assertInstanceOf(Rss::class, $feed);

        $count = 0;
        foreach ($feed as $entry) {
            $this->assertInstanceOf(RssEntry::class, $entry);
            $count++;
        }
        $this->assertGreaterThan(0, $count, 'Feed should have entries');
    }

    public function testRssFeedCountable(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $this->assertGreaterThan(0, count($feed));
    }

    public function testRssFeedFirstEntry(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertInstanceOf(RssEntry::class, $entry);
    }

    public function testRssFeedTitle(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $this->assertNotEmpty($feed->title());
    }

    public function testRssFeedLink(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $this->assertNotEmpty($feed->link());
    }

    public function testRssEntryTitle(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotEmpty($entry->title());
    }

    public function testRssEntryGetContent(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $feed->rewind();
        $entry = $feed->current();
        // RSS may use description, not content
        $this->assertNotEmpty($entry->description());
    }

    public function testRssHttpClientInjection(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $this->assertNotNull($feed->getHttpClient());

        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotNull($entry->httpClient);
    }
}
