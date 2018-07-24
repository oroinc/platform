<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Oro\Bundle\AttachmentBundle\Model\Thumbnail;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Factory\ImagineBinaryByFileContentFactoryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Oro\Bundle\AttachmentBundle\Tools\ThumbnailFactory;

class ThumbnailFactoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ThumbnailFactory
     */
    private $thumbnailFactory;

    /**
     * @var FilterConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterConfig;

    /**
     * @var ImagineBinaryByFileContentFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imagineBinaryFactory;

    /**
     * @var ImagineBinaryFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $imagineBinaryFilter;

    public function setUp()
    {
        $this->filterConfig = $this->createMock(FilterConfiguration::class);
        $this->imagineBinaryFactory
            = $this->createMock(ImagineBinaryByFileContentFactoryInterface::class);
        $this->imagineBinaryFilter = $this->createMock(ImagineBinaryFilterInterface::class);

        $this->thumbnailFactory = new ThumbnailFactory(
            $this->imagineBinaryFactory,
            $this->imagineBinaryFilter,
            $this->filterConfig
        );
    }

    public function testCreateThumbnail()
    {
        $width = 640;
        $height = 480;
        $filter = 'attachment_640_480';
        $content = 'binary_content';

        $binary = $this->createBinaryMock();
        $filteredBinary = $this->createBinaryMock();

        $this->filterConfig
            ->method('set')
            ->with(
                $filter,
                [
                    'filters' => [
                        'thumbnail' => [
                            'size' => [$width, $height],
                        ],
                    ],
                ]
            );

        $this->imagineBinaryFactory
            ->method('createImagineBinary')
            ->with($content)
            ->willReturn($binary);

        $this->imagineBinaryFilter
            ->method('applyFilter')
            ->with($binary, $filter)
            ->willReturn($filteredBinary);

        $this->assertEquals(
            new Thumbnail(
                $filteredBinary,
                $width,
                $height,
                $filter
            ),
            $this->thumbnailFactory->createThumbnail($content, $width, $height)
        );
    }

    /**
     * @return BinaryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createBinaryMock()
    {
        return $this->createMock(BinaryInterface::class);
    }
}
