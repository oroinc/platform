<?php
declare(strict_types=1);

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Oro\Bundle\DashboardBundle\EventListener\WidgetSortByListener;
use Oro\Bundle\DashboardBundle\Model\WidgetConfigs;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class WidgetSortByListenerTest extends OrmTestCase
{
    /**
     * @dataProvider onResultBeforeQueryShouldNotUpdateQueryProvider
     */
    public function testOnResultBeforeQueryShouldNotUpdateQuery(WidgetOptionBag $widgetOptionBag = null)
    {
        /** @var MockObject|WidgetConfigs $widgetConfigs */
        $widgetConfigs = $this->getMockBuilder(WidgetConfigs::class)->disableOriginalConstructor()->getMock();
        $widgetConfigs->method('getWidgetOptions')->willReturn($widgetOptionBag);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(
            new AnnotationDriver(
                new AnnotationReader(),
                'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity'
            )
        );

        $datagrid = $this->createMock(DatagridInterface::class);

        $qb = $em->createQueryBuilder();
        $originalDQL = $qb->getQuery()->getDQL();

        $widgetSortByListener = new WidgetSortByListener($widgetConfigs);
        $widgetSortByListener->onResultBeforeQuery(new OrmResultBeforeQuery($datagrid, $qb));

        static::assertEquals($originalDQL, $qb->getQuery()->getDQL());
    }

    public function onResultBeforeQueryShouldNotUpdateQueryProvider()
    {
        return [
            [new WidgetOptionBag()],
            [new WidgetOptionBag([
                'sortBy' => [
                    'property' => '',
                    'order' => 'ASC',
                    'className' => 'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestClass',
                ],
            ])],
            [new WidgetOptionBag([
                'sortBy' => [
                    'property' => 'nonExisting',
                    'order' => 'ASC',
                    'className' => 'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestClass',
                ]
            ])],
        ];
    }

    /**
     * @dataProvider onResultBeforeQueryShouldUpdateQueryProvider
     */
    public function testOnResultBeforeQueryShouldUpdateQuery(WidgetOptionBag $widgetOptionBag, $expectedDQL)
    {
        /** @var MockObject|WidgetConfigs $widgetConfigs */
        $widgetConfigs = $this->getMockBuilder(WidgetConfigs::class)->disableOriginalConstructor()->getMock();
        $widgetConfigs->method('getWidgetOptions')->willReturn($widgetOptionBag);

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(
            new AnnotationDriver(
                new AnnotationReader(),
                'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity'
            )
        );
        $qb = $em->createQueryBuilder()
            ->select('tc')
            ->from('Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestClass', 'tc')
            ->orderBy('tc.id', 'DESC');

        $datagrid = $this->createMock(DatagridInterface::class);

        $widgetSortByListener = new WidgetSortByListener($widgetConfigs);
        $widgetSortByListener->onResultBeforeQuery(new OrmResultBeforeQuery($datagrid, $qb));

        static::assertEquals($expectedDQL, $qb->getQuery()->getDQL());
    }

    public function onResultBeforeQueryShouldUpdateQueryProvider()
    {
        return [
            [
                new WidgetOptionBag([
                    'sortBy' => [
                        'property' => 'existing',
                        'order' => 'ASC',
                        'className' => 'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestClass',
                    ]
                ]),
                <<<'DQL'
SELECT tc FROM Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity\TestClass tc ORDER BY tc.existing ASC
DQL
            ],
        ];
    }
}
