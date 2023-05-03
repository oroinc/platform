<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Provider;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\FilterBundle\Provider\DatagridFiltersProviderComposite;
use Oro\Bundle\FilterBundle\Provider\DatagridFiltersProviderInterface;

class DatagridFiltersProviderCompositeTest extends \PHPUnit\Framework\TestCase
{
    /** @var DatagridFiltersProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider1;

    /** @var DatagridFiltersProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider2;

    protected function setUp(): void
    {
        $this->innerProvider1 = $this->createMock(DatagridFiltersProviderInterface::class);
        $this->innerProvider2 = $this->createMock(DatagridFiltersProviderInterface::class);
    }

    public function testGetDatagridFilters(): void
    {
        $gridConfig = $this->createMock(DatagridConfiguration::class);
        $filters1 = ['filter1' => ['name' => 'filter1']];
        $filters2 = ['filter2' => ['name' => 'filter2']];

        $this->innerProvider1->expects($this->once())
            ->method('getDatagridFilters')
            ->with($gridConfig)
            ->willReturn($filters1);

        $this->innerProvider2->expects($this->once())
            ->method('getDatagridFilters')
            ->with($gridConfig)
            ->willReturn($filters2);

        $provider = new DatagridFiltersProviderComposite([$this->innerProvider1, $this->innerProvider2]);
        $this->assertEquals(array_merge($filters1, $filters2), $provider->getDatagridFilters($gridConfig));
    }
}
