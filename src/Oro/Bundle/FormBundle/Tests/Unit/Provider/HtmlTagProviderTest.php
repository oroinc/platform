<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Provider;

use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Symfony\Component\Yaml\Yaml;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class HtmlTagProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var HtmlTagProvider */
    private $htmlTagProvider;

    protected function setUp(): void
    {
        $this->htmlTagProvider = new HtmlTagProvider(
            Yaml::parse(file_get_contents(__DIR__ . '/Fixtures/purifier_config.yml'))
        );
    }

    public function testGetScopes()
    {
        self::assertEquals(['default', 'additional', 'extra'], $this->htmlTagProvider->getScopes());
    }

    /**
     * @dataProvider allowedRelDataProvider
     */
    public function testGetAllowedRel(string $scope, array $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->htmlTagProvider->getAllowedRel($scope));
    }

    /**
     * @dataProvider allowedElementsDataProvider
     */
    public function testGetAllowedElements(string $scope, array $expectedResult): void
    {
        $allowedElements = $this->htmlTagProvider->getAllowedElements($scope);
        $this->assertEquals($expectedResult, $allowedElements);
    }

    /**
     * @dataProvider allowedTagsDataProvider
     */
    public function testGetAllowedTags(string $scope, string $expectedResult): void
    {
        $allowedTags = $this->htmlTagProvider->getAllowedTags($scope);
        $this->assertEquals($expectedResult, $allowedTags);
    }

    /**
     * @dataProvider iframeRegexpDataProvider
     */
    public function testGetIframeRegexp(string $scope, string $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->htmlTagProvider->getIframeRegexp($scope));
    }

    public function testGetIframeRegexpBypass()
    {
        $scamUri = 'https://www.scam.com/embed/XWyzuVHRe0A?bypass=https://www.youtube.com/embed/XWyzuVHRe0A';
        $allowedUri = 'https://www.youtube.com/embed/XWyzuVHRe0A';

        $this->assertSame(0, preg_match($this->htmlTagProvider->getIframeRegexp('default'), $scamUri));
        $this->assertSame(1, preg_match($this->htmlTagProvider->getIframeRegexp('default'), $allowedUri));
    }

    /**
     * @dataProvider uriSchemesDataProvider
     */
    public function testGetUriSchemes(string $scope, array $expectedResult): void
    {
        $this->assertEquals($expectedResult, $this->htmlTagProvider->getUriSchemes($scope));
    }

    public function allowedRelDataProvider(): array
    {
        return [
            'default scope' => [
                'scope' => 'default',
                'expectedResult' => []
            ],
            'additional scope' => [
                'scope' => 'additional',
                'expectedResult' => [
                    'alternate',
                    'author',
                    'bookmark'
                ]
            ],
            'extra scope' => [
                'scope' => 'extra',
                'expectedResult' => [
                    'alternate',
                    'author',
                    'bookmark',
                    'help',
                    'nofollow',
                    'opener'
                ]
            ]
        ];
    }

    public function allowedElementsDataProvider(): array
    {
        return [
            'default scope' => [
                'scope' => 'default',
                'expectedResult' => [
                    '@[id|style|class]',
                    'p',
                    'span[id]',
                    'br',
                    'style[media|type]',
                    'iframe[allowfullscreen]',
                    'a[target]'
                ]
            ],
            'additional scope' => [
                'scope' => 'additional',
                'expectedResult' => [
                    '@[id|style|class]',
                    'p',
                    'span[id]',
                    'br',
                    'style[media|type]',
                    'iframe[allowfullscreen]',
                    'a[target|title]',
                    'div',
                ]
            ],
            'extra scope' => [
                'scope' => 'extra',
                'expectedResult' => [
                    '@[id|style|class]',
                    'p',
                    'span[id]',
                    'br',
                    'style[media|type]',
                    'iframe[allowfullscreen]',
                    'a[target|title]',
                    'div',
                    'img'
                ]
            ]
        ];
    }

    public function allowedTagsDataProvider(): array
    {
        return [
            'default scope' => [
                'scope' => 'default',
                'expectedResult' => '<p></p><span></span><br><style></style><iframe></iframe><a></a>'
            ],
            'additional scope' => [
                'scope' => 'additional',
                'expectedResult' => '<p></p><span></span><br><style></style><iframe></iframe><a></a><div></div>'
            ],
            'extra scope' => [
                'scope' => 'extra',
                'expectedResult' => '<p></p><span></span><br><style></style><iframe></iframe><a></a><div></div><img>'
            ]
        ];
    }

    public function iframeRegexpDataProvider(): array
    {
        return [
            'default scope' => [
                'scope' => 'default',
                'expectedResult' => '<^https?://(www.)?(youtube.com/embed/|player.vimeo.com/video/)>'
            ],
            'additional scope' => [
                'scope' => 'additional',
                'expectedResult' => '<^https?://(www.)?(youtube.com/embed/|player.vimeo.com/video/|maps.google.com)>'
            ],
            'extra scope' => [
                'scope' => 'extra',
                'expectedResult' =>
                    '<^https?://(www.)?(youtube.com/embed/|player.vimeo.com/video/|maps.google.com|example.com)>'
            ]
        ];
    }

    public function uriSchemesDataProvider(): array
    {
        return [
            'default scope' => [
                'scope' => 'default',
                'expectedResult' => [
                    'http' => true,
                    'https' => true,
                    'ftp' => true,
                ]
            ],
            'additional scope' => [
                'scope' => 'additional',
                'expectedResult' => [
                    'http' => true,
                    'https' => true,
                    'ftp' => true,
                    'tel' => true,
                ]
            ],
            'extra scope' => [
                'scope' => 'extra',
                'expectedResult' => [
                    'http' => true,
                    'https' => true,
                    'ftp' => true,
                    'tel' => true,
                    'mailto' => true,
                ]
            ]
        ];
    }
}
