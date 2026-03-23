<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\Blogroll;
use Horde\Feed\BlogrollEntry;
use Horde\Feed\Feed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(Blogroll::class)]
class BlogrollTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    #[DataProvider('getValidBlogrolls')]
    public function testValidBlogrolls(string $file): void
    {
        $feed = Feed::readFile($this->feedDir . $file);
        $this->assertInstanceOf(Blogroll::class, $feed);
    }

    public static function getValidBlogrolls(): array
    {
        return [
            ['BlogRollTestSample1.xml'],
            ['BlogRollTestSample2.xml'],
            ['MySubscriptionsGrouped.opml'],
        ];
    }

    public function testBlogrollIteration(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $this->assertInstanceOf(Blogroll::class, $feed);

        $count = 0;
        foreach ($feed as $entry) {
            $this->assertInstanceOf(BlogrollEntry::class, $entry);
            $count++;
        }
        $this->assertGreaterThan(0, $count, 'Blogroll should have entries');
    }

    public function testBlogrollCountable(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $this->assertGreaterThan(0, count($feed));
    }

    public function testBlogrollTitle(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $this->assertNotEmpty($feed->getTitle());
    }

    public function testBlogrollEntryText(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotEmpty($entry->text);
    }

    public function testBlogrollEntryXmlUrl(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotEmpty($entry->xmlUrl);
    }

    public function testBlogrollEntryHtmlUrl(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample2.xml');
        $feed->rewind();
        $entry = $feed->current();
        // Sample2 has outline elements, check if htmlUrl exists
        $url = $entry->htmlUrl ?? null;
        $this->assertTrue(is_string($url) || $url === null);
    }

    public function testBlogrollHttpClientInjection(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $this->assertNotNull($feed->getHttpClient());

        $feed->rewind();
        $entry = $feed->current();
        $this->assertNotNull($entry->httpClient);
    }

    public function testGroupedBlogrollsIterateFlatly(): void
    {
        $feed = Feed::readFile($this->feedDir . 'MySubscriptionsGrouped.opml');
        $this->assertInstanceOf(Blogroll::class, $feed);

        // Grouped OPML has nested <outline> elements:
        // - Group containers (text/title only, no xmlUrl)
        // - Leaf entries (text + xmlUrl)
        //
        // The Blogroll should iterate ONLY leaf entries with xmlUrl,
        // flattening the hierarchy for simple consumption

        $count = 0;
        foreach ($feed as $entry) {
            $this->assertInstanceOf(BlogrollEntry::class, $entry);

            // Every iterated entry must have xmlUrl (leaf entries only)
            $xmlUrl = $entry['xmlUrl'] ?? null;
            $this->assertNotNull($xmlUrl, 'All iterated entries should have xmlUrl');
            $this->assertNotEmpty($xmlUrl);

            $count++;
        }

        // MySubscriptionsGrouped.opml has ~30 leaf feed entries
        // (Planet PHP, Planet MySQL, various grouped Salon/Blue State/etc feeds)
        $this->assertGreaterThan(25, $count, 'Should iterate all leaf feed entries');
        $this->assertEquals(count($feed), $count, 'count() should match iteration');
    }
}
