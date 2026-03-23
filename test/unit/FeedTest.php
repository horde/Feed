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
use Horde\Feed\Exception;
use Horde\Feed\Feed;
use Horde\Feed\FeedBase;
use Horde\Feed\Rss;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(Feed::class)]
class FeedTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    #[DataProvider('getValidAtomTests')]
    public function testValidAtomFeeds(string $file): void
    {
        $feed = Feed::readFile($this->feedDir . $file);
        $this->assertInstanceOf(Atom::class, $feed);
    }

    public static function getValidAtomTests(): array
    {
        return [
            ['AtomTestGoogle.xml'],
            ['AtomTestMozillazine.xml'],
            ['AtomTestOReilly.xml'],
            ['AtomTestPlanetPHP.xml'],
            ['AtomTestSample1.xml'],
            ['AtomTestSample2.xml'],
            ['AtomTestSample4.xml'],
        ];
    }

    #[DataProvider('getValidRssTests')]
    public function testValidRssFeeds(string $file): void
    {
        $feed = Feed::readFile($this->feedDir . $file);
        $this->assertInstanceOf(Rss::class, $feed);
    }

    public static function getValidRssTests(): array
    {
        return [
            ['RssTestHarvardLaw.xml'],
            ['RssTestPlanetPHP.xml'],
            ['RssTestSlashdot.xml'],
            ['RssTestCNN.xml'],
            ['RssTest091Sample1.xml'],
            ['RssTest092Sample1.xml'],
            ['RssTest100Sample1.xml'],
            ['RssTest100Sample2.xml'],
            ['RssTest200Sample1.xml'],
        ];
    }

    public function testAtomWithUnbalancedTags(): void
    {
        $feed = Feed::readFile($this->feedDir . 'AtomTestSample3.xml');
        $this->assertInstanceOf(FeedBase::class, $feed);
    }

    public function testNotAFeed(): void
    {
        $this->expectException(Exception::class);
        $feed = Feed::readFile($this->feedDir . 'NotAFeed.xml');
    }
}
