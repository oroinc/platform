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
        $this->assertEquals([
            '@[style|class]', 'p', 'span[id]', 'br', 'style[media|type]', 'iframe[allowfullscreen]'
        ], $allowedElements);
    }

    public function testGetAllowedTags()
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags();
        $this->assertEquals('<p></p><span></span><br><style></style><iframe></iframe>', $allowedTags);
    }

    public function testGetIframeRegexp()
    {
        $htmlTagProvider = new HtmlTagProvider([], [
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

        $htmlTagProvider = new HtmlTagProvider([], [
            'youtube.com/embed/',
            'player.vimeo.com/video/',
        ]);

        $this->assertSame(0, preg_match($htmlTagProvider->getIframeRegexp(), $scamUri));
        $this->assertSame(1, preg_match($htmlTagProvider->getIframeRegexp(), $allowedUri));
    }

    public function testGetIframeRegexpEmpty()
    {
        $htmlTagProvider = new HtmlTagProvider([]);

        $this->assertEquals('', $htmlTagProvider->getIframeRegexp());
    }

    public function testGetUriSchemes()
    {
        $htmlTagProvider = new HtmlTagProvider([], [], [
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
        $htmlTagProvider = new HtmlTagProvider([]);

        $this->assertEquals([], $htmlTagProvider->getUriSchemes());
    }
}
