<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */
namespace Horde\Feed;
use PHPUnit\Framework\TestCase;
use \Horde_Feed;

class AtomEntryOnlyTest extends TestCase {

    public function testEntryOnly()
    {
        $feed = Horde_Feed::readFile(__DIR__ . '/fixtures/TestAtomFeedEntryOnly.xml');

        $this->assertEquals(1, $feed->count(), 'The entry-only feed should report one entry.');

        foreach ($feed as $entry);
        $this->assertEquals('Horde_Feed_Entry_Atom', get_class($entry), 'The single entry should be an instance of Horde_Feed_Entry_Atom');

        $this->assertEquals('1', $entry->id(), 'The single entry should have id 1');
        $this->assertEquals('Bug', $entry->title(), 'The entry\'s title should be "Bug"');
    }

}
