<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Filter\Basic;

use Liip\ImagineBundle\Binary\BinaryInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\Basic\BasicImagineBinaryFilter;

class BasicImagineBinaryFilterTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FilterManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $filterManager;

    /**
     * @var BasicImagineBinaryFilter
     */
    private $filter;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->filterManager = $this->createMock(FilterManager::class);

        $this->filter = new BasicImagineBinaryFilter($this->filterManager);
    }

    public function testApplyFilter()
    {
        $binary = $this->createBinaryMock();
        $filter = 'category_medium';

        $resultBinary = $this->createBinaryMock();

        $this->filterManager
            ->method('applyFilter')
            ->with($binary, $filter)
            ->willReturn($resultBinary);

        $this->filter->applyFilter($binary, $filter);
    }

    /**
     * @return BinaryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createBinaryMock()
    {
        return $this->createMock(BinaryInterface::class);
    }
}
