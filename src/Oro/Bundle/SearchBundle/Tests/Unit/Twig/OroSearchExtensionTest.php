<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Twig;

use Oro\Bundle\SearchBundle\Twig\OroSearchExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;

class OroSearchExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var OroSearchExtension */
    private $extension;

    protected function setUp(): void
    {
        $twigService = $this->createMock(Environment::class);
        $this->extension = new OroSearchExtension($twigService, 'testLayout.html.twig');
    }

    public function testHighlight()
    {
        $result = self::callTwigFilter($this->extension, 'highlight', ['test search string', 'search']);
        $this->assertEquals(5, strpos($result, '<strong>search</strong>'));
    }

    public function testTrimByString()
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

    public function testHighlightTrim()
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
