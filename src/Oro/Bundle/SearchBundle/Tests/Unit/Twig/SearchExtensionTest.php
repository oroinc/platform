<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Twig;

use Oro\Bundle\SearchBundle\Twig\SearchExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\TestCase;

class SearchExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private SearchExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new SearchExtension();
    }

    public function testHighlight(): void
    {
        $result = self::callTwigFilter($this->extension, 'highlight', ['test search string', 'search']);
        $this->assertEquals(5, strpos($result, '<strong>search</strong>'));
    }

    public function testTrimByString(): void
    {
        $this->assertEquals(
            '...Writing Tests search string...',
            self::callTwigFilter(
                $this->extension,
                'trim_string',
                ['Writing Tests for PHPUnit search string The tests', 'search string', 15]
            )
        );
    }

    public function testHighlightTrim(): void
    {
        $this->assertEquals(
            '...Writing Tests <strong>search</strong> string...',
            self::callTwigFilter(
                $this->extension,
                'highlight_trim',
                ['Writing Tests for PHPUnit search string The tests', 'search', 15]
            )
        );
    }
}
