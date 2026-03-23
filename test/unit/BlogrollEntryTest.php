<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\BlogrollEntry;
use Horde\Feed\Exception;
use Horde\Feed\Feed;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(BlogrollEntry::class)]
class BlogrollEntryTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    public function testCreateEntryFromNull(): void
    {
        $entry = new BlogrollEntry();
        $this->assertNotNull($entry->httpClient);
        $this->assertInstanceOf(BlogrollEntry::class, $entry);
    }

    public function testCreateEntryFromArray(): void
    {
        $data = [
            'text' => 'Test Blog',
            'xmlUrl' => 'http://example.com/feed.xml',
        ];

        $entry = new BlogrollEntry($data);
        $this->assertEquals('Test Blog', $entry->text);
    }

    public function testGetFeedRequiresXmlUrl(): void
    {
        $xml = '<?xml version="1.0"?><outline text="Test"/>';
        $entry = new BlogrollEntry($xml);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No XML URL');
        $entry->getFeed();
    }

    public function testEntryHasXmlUrl(): void
    {
        $xml = '<?xml version="1.0"?><outline text="Test" xmlUrl="http://example.com/feed.xml"/>';
        $entry = new BlogrollEntry($xml);

        $this->assertEquals('http://example.com/feed.xml', $entry->xmlUrl);
    }

    public function testEntryAttributes(): void
    {
        $feed = Feed::readFile($this->feedDir . 'BlogRollTestSample1.xml');
        $feed->rewind();
        $entry = $feed->current();

        $this->assertNotEmpty($entry->text);
        $this->assertNotEmpty($entry->xmlUrl);
    }
}
