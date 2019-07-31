<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;

class HtmlTagProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var array */
    private $elements;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->elements = [
            'p' => [],
            'span' => [
                'attributes' => ['id']
            ],
            'br' => [
                'hasClosingTag' => false
            ],
            'style' => [
                'attributes' => ['media', 'type']
            ],
            'iframe' => [
                'attributes' => ['allowfullscreen']
            ],
        ];

        $this->htmlTagProvider = new HtmlTagProvider($this->elements);
    }

    public function testGetAllowedElementsDefault()
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements();
        $this->assertEquals(['@[style|class]', 'p', 'span[id]', 'br'], $allowedElements);
    }

    public function testGetAllowedExtended()
    {
        $htmlTagProvider = new HtmlTagProvider($this->elements, HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED);
        $allowedElements = $htmlTagProvider->getAllowedElements();
        $this->assertEquals(
            ['@[style|class]', 'p', 'span[id]', 'br', 'style[media|type]', 'iframe[allowfullscreen]'],
            $allowedElements
        );
    }

    public function testGetAllowedTags()
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags();
        $this->assertEquals('<p></p><span></span><br>', $allowedTags);
    }

    public function testIsPurificationNeededDefault()
    {
        $this->assertTrue($this->htmlTagProvider->isPurificationNeeded());
    }

    public function testIsPurificationNeededStrict()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_STRICT);
        $this->assertTrue($htmlTagProvider->isPurificationNeeded());
    }

    public function testIsPurificationNeededExtended()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED);
        $this->assertTrue($htmlTagProvider->isPurificationNeeded());
    }

    public function testIsPurificationNeededDisabled()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_DISABLED);
        $this->assertFalse($htmlTagProvider->isPurificationNeeded());
    }

    public function testIsExtendedPurificationDefault()
    {
        $this->assertFalse($this->htmlTagProvider->isExtendedPurification());
    }

    public function testIsExtendedPurificationExtended()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED);
        $this->assertTrue($htmlTagProvider->isExtendedPurification());
    }

    public function testGetIframeRegexp()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED, [
            'youtube.com/embed/',
            'player.vimeo.com/video/',
        ]);

        $this->assertEquals(
            '<^https?://(www.)?(youtube.com/embed/|player.vimeo.com/video/)>',
            $htmlTagProvider->getIframeRegexp()
        );
    }

    public function testGetIframeRegexpBypass()
    {
        $scamUri = 'https://www.scam.com/embed/XWyzuVHRe0A?bypass=https://www.youtube.com/embed/XWyzuVHRe0A';
        $allowedUri = 'https://www.youtube.com/embed/XWyzuVHRe0A';

        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED, [
            'youtube.com/embed/',
            'player.vimeo.com/video/',
        ]);

        $this->assertSame(0, preg_match($htmlTagProvider->getIframeRegexp(), $scamUri));
        $this->assertSame(1, preg_match($htmlTagProvider->getIframeRegexp(), $allowedUri));
    }

    public function testGetIframeRegexpEmpty()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED);

        $this->assertEquals('', $htmlTagProvider->getIframeRegexp());
    }

    public function testGetUriSchemes()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED, [], [
            'http',
            'https',
            'ftp',
        ]);

        $this->assertEquals(
            [
                'http' => true,
                'https' => true,
                'ftp' => true,
            ],
            $htmlTagProvider->getUriSchemes()
        );
    }

    public function testGetUriSchemesEmpty()
    {
        $htmlTagProvider = new HtmlTagProvider([], HtmlTagProvider::HTML_PURIFIER_MODE_EXTENDED);

        $this->assertEquals([], $htmlTagProvider->getUriSchemes());
    }
}
