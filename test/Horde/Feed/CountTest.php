<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */
class Horde_Feed_CountTest extends Horde_Test_Case {

    public function testCount()
    {
        $f = Horde_Feed::readFile(__DIR__ . '/fixtures/TestAtomFeed.xml');
        $this->assertEquals($f->count(), 2, 'Feed count should be 2');
    }

}
