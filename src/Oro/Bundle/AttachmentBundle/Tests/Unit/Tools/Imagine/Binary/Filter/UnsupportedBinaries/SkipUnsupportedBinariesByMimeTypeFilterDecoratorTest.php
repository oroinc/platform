<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\ImagineBinaryFilterInterface;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\UnsupportedBinaries;

class SkipUnsupportedBinariesByMimeTypeFilterDecoratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImagineBinaryFilterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $decoratedFilter;

    /** @var UnsupportedBinaries\SkipUnsupportedBinariesByMimeTypeFilterDecorator */
    private $filterDecorator;

    protected function setUp(): void
    {
        $this->decoratedFilter = $this->createMock(ImagineBinaryFilterInterface::class);

        $this->filterDecorator = new UnsupportedBinaries\SkipUnsupportedBinariesByMimeTypeFilterDecorator(
            $this->decoratedFilter,
            ['image/png']
        );
    }

    public function testApplyFilterSupported()
    {
        $binary = $this->createMock(BinaryInterface::class);
        $filter = 'product_medium';

        $binary->expects(self::any())
            ->method('getMimeType')
            ->willReturn('image/jpg');

        $this->decoratedFilter->expects(self::any())
            ->method('applyFilter')
            ->with($binary, $filter);

        $this->filterDecorator->applyFilter($binary, $filter);
    }

    public function testApplyFilterNotSupported()
    {
        $binary = $this->createMock(BinaryInterface::class);
        $filter = 'product_medium';

        $binary->expects(self::any())
            ->method('getMimeType')
            ->willReturn('image/png');

        $this->decoratedFilter->expects(self::never())
            ->method('applyFilter');

        $this->filterDecorator->applyFilter($binary, $filter);
    }
}
