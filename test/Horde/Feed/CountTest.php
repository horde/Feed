<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */
namespace Horde\Feed;
use PHPUnit\Framework\TestCase;
use \Horde_Feed;

class CountTest extends TestCase {

    public function testCount()
    {
        $f = Horde_Feed::readFile(__DIR__ . '/fixtures/TestAtomFeed.xml');
        $this->assertEquals($f->count(), 2, 'Feed count should be 2');
    }

}
