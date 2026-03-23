<?php

/**
 * @category Horde
 * @package Feed
 * @subpackage UnitTests
 */

namespace Horde\Feed;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use DirectoryIterator;
use Horde_Feed;

/**
 * @coversNothing
 */
class LexiconTest extends TestCase
{
    #[DataProvider('getLexicon')]
    public function testParse($file)
    {
        $feed = Horde_Feed::readFile($file);
        $this->assertGreaterThan(0, count($feed));
    }

    public static function getLexicon()
    {
        $files = [];
        foreach (new DirectoryIterator(__DIR__ . '/fixtures/lexicon') as $file) {
            if ($file->isFile()) {
                $files[] = [$file->getPathname()];
            }
        }

        return $files;
    }

}
