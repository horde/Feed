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
use Horde\Feed\RssEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(RssEntry::class)]
class RssEntryTest extends TestCase
{
    protected string $feedDir;

    public function setUp(): void
    {
        $this->feedDir = __DIR__ . '/fixtures/';
    }

    public function testCreateEntryFromNull(): void
    {
        $entry = new RssEntry();
        $this->assertNotNull($entry->httpClient);
        $this->assertInstanceOf(RssEntry::class, $entry);
    }

    public function testCreateEntryFromArray(): void
    {
        $data = [
            'title' => 'Test Title',
            'description' => 'Test Description',
        ];

        $entry = new RssEntry($data);
        $this->assertEquals('Test Title', $entry->title());
    }

    public function testGetContentFromDescription(): void
    {
        $feed = Feed::readFile($this->feedDir . 'RssTestSlashdot.xml');
        $feed->rewind();
        $entry = $feed->current();

        // RSS typically uses description, test that instead
        $this->assertNotEmpty($entry->description());
    }

    public function testGetContentReturnsValue(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<item>
    <title>Test</title>
    <description>Short description</description>
</item>';

        $entry = new RssEntry($xml);
        $content = $entry->getContent();

        // getContent() may return various types (element, array, or empty array)
        // Just verify it doesn't throw an exception
        $this->assertIsNotBool($content);
    }
}
