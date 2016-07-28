<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\EventListener;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

use Oro\Bundle\DashboardBundle\EventListener\WidgetSortByListener;
use Oro\Bundle\DashboardBundle\Model\WidgetOptionBag;
use Oro\Bundle\DataGridBundle\Event\OrmResultBeforeQuery;
use Oro\Component\TestUtils\ORM\OrmTestCase;

class WidgetSortByListenerTest extends OrmTestCase
{
    /**
     * @dataProvider onResultBeforeQueryShouldNotUpdateQueryProvider
     */
    public function testOnResultBeforeQueryShouldNotUpdateQuery(WidgetOptionBag $widgetOptionBag = null)
    {
        $widgetConfigs = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetConfigs')
            ->disableOriginalConstructor()
            ->getMock();
        $widgetConfigs->expects($this->any())
            ->method('getWidgetOptions')
            ->will($this->returnValue($widgetOptionBag));

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl(
            new AnnotationDriver(
                new AnnotationReader(),
                'Oro\Bundle\DashboardBundle\Tests\Unit\Fixtures\Entity'
            )
        );

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $qb = $em->createQueryBuilder();
        $originalDQL = $qb->getQuery()->getDQL();

        $widgetSortByListener = new WidgetSortByListener($widgetConfigs);
        $widgetSortByListener->onResultBeforeQuery(new OrmResultBeforeQuery($datagrid, $qb));

        $this->assertEquals($originalDQL, $qb->getQuery()->getDQL());
    }

    public function onResultBeforeQueryShouldNotUpdateQueryProvider()
    {
        return [
            [null],
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
        $widgetConfigs = $this->getMockBuilder('Oro\Bundle\DashboardBundle\Model\WidgetConfigs')
            ->disableOriginalConstructor()
            ->getMock();
        $widgetConfigs->expects($this->any())
            ->method('getWidgetOptions')
            ->will($this->returnValue($widgetOptionBag));

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

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $widgetSortByListener = new WidgetSortByListener($widgetConfigs);
        $widgetSortByListener->onResultBeforeQuery(new OrmResultBeforeQuery($datagrid, $qb));

        $this->assertEquals($expectedDQL, $qb->getQuery()->getDQL());
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
