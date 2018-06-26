<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

class SkipUnsupportedBinariesByMimeTypeFilterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ImagineBinaryFilterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $decoratedFilter;

    /**
     * @var array
     */
    private $unsupportedMimeTypes;

    /**
     * @var UnsupportedBinaries\SkipUnsupportedBinariesByMimeTypeFilterDecorator
     */
    private $filterDecorator;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->decoratedFilter = $this->createMock(ImagineBinaryFilterInterface::class);
        $this->unsupportedMimeTypes = [
            'image/png'
        ];

        $this->filterDecorator = new UnsupportedBinaries\SkipUnsupportedBinariesByMimeTypeFilterDecorator(
            $this->decoratedFilter,
            $this->unsupportedMimeTypes
        );
    }

    public function testApplyFilterSupported()
    {
        $binary = $this->createBinaryMock();
        $filter = 'product_medium';

        $binary->method('getMimeType')
            ->willReturn('image/jpg');

        $this->decoratedFilter->method('applyFilter')
            ->with($binary, $filter);

        $this->filterDecorator->applyFilter($binary, $filter);
    }

    public function testApplyFilterNotSupported()
    {
        $binary = $this->createBinaryMock();
        $filter = 'product_medium';

        $binary->method('getMimeType')
            ->willReturn('image/png');

        $this->decoratedFilter->expects(static::never())
            ->method('applyFilter');

        $this->filterDecorator->applyFilter($binary, $filter);
    }

    /**
     * @return BinaryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createBinaryMock()
    {
        return $this->createMock(BinaryInterface::class);
    }
}
