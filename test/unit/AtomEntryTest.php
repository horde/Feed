<?php

declare(strict_types=1);

/**
 * Copyright 2007-2026 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://www.horde.org/licenses/bsd.
 */

namespace Horde\Feed\Test;

use Horde\Feed\AtomEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @author   Chuck Hagenbuch <chuck@horde.org>
 * @category Horde
 * @package  Feed
 * @subpackage UnitTests
 */
#[CoversClass(AtomEntry::class)]
class AtomEntryTest extends TestCase
{
    public function testCreateEntryFromNull(): void
    {
        $entry = new AtomEntry();
        $this->assertNotNull($entry->httpClient);
        $this->assertInstanceOf(AtomEntry::class, $entry);
    }

    public function testCreateEntryFromArray(): void
    {
        $data = [
            'title' => 'Test Title',
            'content' => 'Test Content',
        ];

        $entry = new AtomEntry($data);
        $this->assertEquals('Test Title', $entry->title());
    }

    public function testEntryLinkMethod(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>test-id</id>
    <title>Test</title>
    <link rel="alternate" href="http://example.com/1"/>
    <link rel="edit" href="http://example.com/entry/1"/>
</entry>';

        $entry = new AtomEntry($xml);
        $alternate = $entry->link('alternate');
        $edit = $entry->link('edit');

        $this->assertEquals('http://example.com/1', $alternate);
        $this->assertEquals('http://example.com/entry/1', $edit);
    }

    public function testEntrySaveXml(): void
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<entry xmlns="http://www.w3.org/2005/Atom">
    <id>test-id</id>
    <title>Test</title>
</entry>';

        $entry = new AtomEntry($xml);
        $savedXml = $entry->saveXml();

        $this->assertStringContainsString('<id>test-id</id>', $savedXml);
        $this->assertStringContainsString('<title>Test</title>', $savedXml);
    }
}
