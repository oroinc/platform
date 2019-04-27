<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\Loader\ByFileNameMatcher;

class ByFileNameMatcherTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $fileName
     *
     * @return \SplFileInfo
     */
    private function getFile($fileName)
    {
        $path = realpath(__DIR__ . '/../Fixtures/Bundle/TestBundle1/Resources/folder_to_track/' . $fileName);
        self::assertFileExists($path);

        return new \SplFileInfo($path);
    }

    public function testIsMatchedWhenNoPatterns()
    {
        $matcher = new ByFileNameMatcher([]);

        self::assertTrue($matcher->isMatched($this->getFile('test.xml')));
    }

    public function testIsMatchedWithPatterns()
    {
        $matcher = new ByFileNameMatcher(['/\.yml$/', '/\.xml$/']);

        self::assertTrue($matcher->isMatched($this->getFile('test.xml')));
        self::assertFalse($matcher->isMatched($this->getFile('test.txt')));
    }

    public function testSerialization()
    {
        $matcher = new ByFileNameMatcher(['/\.yml$/', '/\.xml$/']);

        $unserialized = unserialize(serialize($matcher));
        $this->assertEquals($matcher, $unserialized);
        $this->assertNotSame($matcher, $unserialized);
    }
}
