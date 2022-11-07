<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Imagine\Provider;

use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProvider;
use Oro\Bundle\AttachmentBundle\Imagine\Provider\ImagineUrlProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ImagineUrlProviderTest extends \PHPUnit\Framework\TestCase
{
    private UrlGeneratorInterface|\PHPUnit\Framework\MockObject\MockObject $urlGenerator;

    private ImagineUrlProviderInterface $provider;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $filterConfiguration = $this->createMock(FilterConfiguration::class);
        $filenameExtensionHelper = new FilenameExtensionHelper(['image/svg']);

        $this->provider = new ImagineUrlProvider($this->urlGenerator, $filterConfiguration, $filenameExtensionHelper);

        $filterConfiguration
            ->expects(self::any())
            ->method('get')
            ->willReturnMap([['jpeg_filter', ['format' => 'jpeg']], ['empty_filter', []]]);
    }

    /**
     * @dataProvider getFilteredImageUrlDataProvider
     */
    public function testGetFilteredImageUrl(
        string $path,
        string $filterName,
        string $format,
        string $expectedPath
    ): void {
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;

        $url = '/sample/image.img';
        $this->urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('oro_imagine_filter', ['path' => $expectedPath, 'filter' => $filterName], $referenceType)
            ->willReturn($url);

        self::assertEquals($url, $this->provider->getFilteredImageUrl($path, $filterName, $format, $referenceType));
    }

    public function getFilteredImageUrlDataProvider(): array
    {
        return [
            'empty path' => ['path' => '', 'filterName' => 'empty_filter', 'format' => '', 'expected_path' => ''],
            'jpeg extension is added if filter has jpeg format' => [
                'path' => '/sample/image.img',
                'filterName' => 'jpeg_filter',
                'format' => '',
                'expected_path' => 'sample/image.img.jpeg',
            ],
            'extension is not added if filter has no format' => [
                'path' => '/sample/image.img',
                'filterName' => 'empty_filter',
                'format' => '',
                'expected_path' => 'sample/image.img',
            ],
            'jpeg extension is not added if filter has jpeg format but path already has it' => [
                'path' => '/sample/image.jpg',
                'filterName' => 'jpeg_filter',
                'format' => '',
                'expected_path' => 'sample/image.jpg',
            ],
            'webp extension is added even if filter has no format' => [
                'path' => '/sample/image.img',
                'filterName' => 'empty_filter',
                'format' => 'webp',
                'expected_path' => 'sample/image.img.webp',
            ],
            'webp extension is added even if filter has jpeg format' => [
                'path' => '/sample/image.img',
                'filterName' => 'jpeg_filter',
                'format' => 'webp',
                'expected_path' => 'sample/image.img.webp',
            ],
            'webp extension is not added even if path already has it' => [
                'path' => '/sample/image.webp',
                'filterName' => 'empty_filter',
                'format' => 'webp',
                'expected_path' => 'sample/image.webp',
            ],
            'extension is not added for unsupported mime type' => [
                'path' => '/sample/image.svg',
                'filterName' => 'webp_filter',
                'format' => 'webp',
                'expected_path' => 'sample/image.svg',
            ],
        ];
    }
}
