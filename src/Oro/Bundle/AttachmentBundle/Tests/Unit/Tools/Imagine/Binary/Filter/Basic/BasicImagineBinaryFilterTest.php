<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Tools\Imagine\Binary\Filter\Basic;

use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Model\Binary;
use Oro\Bundle\AttachmentBundle\Tools\Imagine\Binary\Filter\Basic\BasicImagineBinaryFilter;

class BasicImagineBinaryFilterTest extends \PHPUnit\Framework\TestCase
{
    private FilterManager|\PHPUnit\Framework\MockObject\MockObject $filterManager;

    private BasicImagineBinaryFilter $filter;

    protected function setUp(): void
    {
        $this->filterManager = $this->createMock(FilterManager::class);

        $this->filter = new BasicImagineBinaryFilter($this->filterManager);
    }

    public function testApplyFilter(): void
    {
        $binary = new Binary('sample_binary', 'image/png');
        $filter = 'category_medium';
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $resultBinary = new Binary('sample_result_binary', 'image/png');

        $this->filterManager
            ->expects(self::once())
            ->method('applyFilter')
            ->with($binary, $filter, $runtimeConfig)
            ->willReturn($resultBinary);

        self::assertSame($resultBinary, $this->filter->applyFilter($binary, $filter, $runtimeConfig));
    }

    public function testApply(): void
    {
        $binary = new Binary('sample_binary', 'image/png');
        $runtimeConfig = ['sample_key' => 'sample_value'];

        $resultBinary = new Binary('sample_result_binary', 'image/png');

        $this->filterManager
            ->expects(self::once())
            ->method('apply')
            ->with($binary, $runtimeConfig)
            ->willReturn($resultBinary);

        self::assertSame($resultBinary, $this->filter->apply($binary, $runtimeConfig));
    }
}
