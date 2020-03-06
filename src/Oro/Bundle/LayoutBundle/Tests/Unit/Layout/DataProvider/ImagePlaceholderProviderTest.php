<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\ImagePlaceholderProvider;
use Oro\Bundle\LayoutBundle\Provider\Image\ImagePlaceholderProviderInterface;

class ImagePlaceholderProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImagePlaceholderProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $imagePlaceholderProvider;

    /** @var ImagePlaceholderProvider */
    private $placeholderDataProvider;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->imagePlaceholderProvider = $this->createMock(ImagePlaceholderProviderInterface::class);
        $this->placeholderDataProvider = new ImagePlaceholderProvider($this->imagePlaceholderProvider);
    }

    /**
     * @dataProvider pathDataProvider
     *
     * @param string|null $path
     * @param string $expectedPath
     */
    public function testGetPath(?string $path, string $expectedPath): void
    {
        $this->imagePlaceholderProvider
            ->expects($this->once())
            ->method('getPath')
            ->with('filter')
            ->willReturn($path);

        $this->assertEquals($expectedPath, $this->placeholderDataProvider->getPath('filter'));
    }

    /**
     * @return array
     */
    public function pathDataProvider(): array
    {
        return [
            'string path' => [
                'path' => '/path',
                'expectedPath' => '/path',
            ],
            'null path' => [
                'path' => null,
                'expectedPath' => '',
            ],
        ];
    }
}
