<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Yaml\Yaml;

class HtmlTagProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagProvider */
    protected $htmlTagProvider;

    /** @var array */
    private $purifierConfig;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->purifierConfig = Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/purifier_config.yml'));

        $this->htmlTagProvider = new HtmlTagProvider($this->purifierConfig);
    }

    public function testGetAllowedElementsDefault()
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements('default');
        $this->assertEquals(
            [
                '@[style|class]',
                'p',
                'span[id]',
                'br',
                'style[media|type]',
                'iframe[allowfullscreen]'
            ],
            $allowedElements
        );
    }

    public function testGetAllowedTags()
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags('default');
        $this->assertEquals('<p></p><span></span><br><style></style><iframe></iframe>', $allowedTags);
    }

    public function testGetIframeRegexp()
    {
        $this->assertEquals(
            '<^https?://(www.)?(youtube.com/embed/|player.vimeo.com/video/)>',
            $this->htmlTagProvider->getIframeRegexp('default')
        );
    }

    public function testGetIframeRegexpBypass()
    {
        $scamUri = 'https://www.scam.com/embed/XWyzuVHRe0A?bypass=https://www.youtube.com/embed/XWyzuVHRe0A';
        $allowedUri = 'https://www.youtube.com/embed/XWyzuVHRe0A';

        $this->assertSame(0, preg_match($this->htmlTagProvider->getIframeRegexp('default'), $scamUri));
        $this->assertSame(1, preg_match($this->htmlTagProvider->getIframeRegexp('default'), $allowedUri));
    }

    public function testGetUriSchemes()
    {
        $this->assertEquals(
            [
                'http' => true,
                'https' => true,
                'ftp' => true,
            ],
            $this->htmlTagProvider->getUriSchemes('default')
        );
    }
}
