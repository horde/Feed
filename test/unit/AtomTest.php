<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\Atom;
use Horde\Feed\AtomEntry;
use Horde\Feed\Feed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(Atom::class)]
class AtomTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    public function testAtomFeedIteration(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $this->assertInstanceOf(Atom::class, $feed);

        $count = 0;
        foreach ($feed as $entry) {
            $this->assertInstanceOf(AtomEntry::class, $entry);
            $count++;
        }
        $this->assertGreaterThan(0, $count, 'Feed should have entries');
    }

    public function testAtomFeedCountable(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $this->assertGreaterThan(0, count($feed));
    }

    public function testAtomFeedFirstEntry(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertInstanceOf(AtomEntry::class, $entry);
    }

    public function testAtomFeedTitle(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestGoogle.xml');
        $this->assertNotEmpty($feed->title());
    }

    public function testAtomFeedLink(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestGoogle.xml');
        $link = $feed->link();
        $this->assertNotEmpty($link);
    }

    public function testAtomFeedLinkRel(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $alternate = $feed->link('alternate');
        $this->assertIsString($alternate);
    }

    public function testAtomEntryTitle(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotEmpty($entry->title());
    }

    public function testAtomEntryContent(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertTrue(isset($entry->content) || isset($entry->summary));
    }

    public function testAtomHttpClientInjection(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $this->assertNotNull($feed->getHttpClient());

        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotNull($entry->httpClient);
    }

    public function testAtomGetUri(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample1.xml');
        $this->assertNull($feed->getUri());
    }
}
