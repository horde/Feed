<?php
/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */
namespace Horde\Feed;
use PHPUnit\Framework\TestCase;
use \Horde_Feed;

class BlogrollTest extends TestCase
{
    protected $_feedDir;

    public function setUp(): void
    {
        $this->_feedDir = __DIR__ . '/fixtures/';
    }

    /**
     * @dataProvider getValidBlogrollTests
     */
    public function testValidBlogrolls($file)
    {
        $feed = Horde_Feed::readFile($this->_feedDir . $file);
        $this->assertInstanceOf('Horde_Feed_Blogroll', $feed);
        $this->assertTrue(count($feed) > 0);
        foreach ($feed as $entry) {
            break;
        }

        $this->assertInstanceOf('Horde_Feed_Entry_Blogroll', $entry);
        $this->assertGreaterThan(0, strlen($entry->text));
        $this->assertGreaterThan(0, strlen($entry->xmlUrl));

        $this->assertEquals($entry->text, $entry['text']);
        $this->assertEquals($entry->description, $entry['description']);
        $this->assertEquals($entry->title, $entry['title']);
        $this->assertEquals($entry->htmlUrl, $entry['htmlUrl']);
        $this->assertEquals($entry->xmlUrl, $entry['xmlUrl']);
    }

    public function testGroupedBlogrolls()
    {
        $feed = Horde_Feed::readFile($this->_feedDir . 'MySubscriptionsGrouped.opml');
        $this->markTestIncomplete();
    }

    public static function getValidBlogrollTests()
    {
        return array(
            array('BlogRollTestSample1.xml'),
            array('BlogRollTestSample2.xml'),
            array('MySubscriptions.opml'),
            array('MySubscriptionsGrouped.opml'),
        );
    }
}
