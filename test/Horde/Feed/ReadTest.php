<?php

/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

namespace Horde\Feed;

use PHPUnit\Framework\TestCase;
use Horde_Feed;
use Horde_Feed_Base;

/**
 * @coversNothing
 */
class ReadTest extends TestCase
{
    protected $_feedDir;

    public function setUp(): void
    {
        $this->_feedDir = __DIR__ . '/fixtures/';
    }

    /**
     * @dataProvider getValidAtomTests
     */
    public function testValidAtomFeeds($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertInstanceOf('Horde_Feed_Atom', $feed);
    }

    public static function getValidAtomTests()
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

    /**
     * @dataProvider getValidRssTests
     */
    public function testValidRssFeeds($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertInstanceOf('Horde_Feed_Rss', $feed);
    }

    public static function getValidRssTests()
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

    public function testAtomWithUnbalancedTags()
    {
        $feed = Horde_Feed::readFile($this->_feedDir . 'AtomTestSample3.xml');
        $this->assertTrue($feed instanceof Horde_Feed_Base, 'Should be able to parse a feed with unmatched tags');
    }

    public function testNotAFeed()
    {
        $this->expectException('Horde_Feed_Exception');

        $feed = Horde_Feed::readFile($this->_feedDir . 'NotAFeed.xml');
    }
}
