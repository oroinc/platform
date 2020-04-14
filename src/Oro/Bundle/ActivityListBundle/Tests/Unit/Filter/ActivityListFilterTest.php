<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;
use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper;
use Oro\Bundle\ActivityListBundle\Filter\DatagridHelper;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\Query;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager as QueryDesignerManager;
use Oro\Component\TestUtils\Mocks\ServiceLink;
use Symfony\Component\Form\FormFactoryInterface;

class ActivityListFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityManager */
    private $em;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryBuilder */
    private $qb;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    private $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|FilterUtility */
    private $filterUtility;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActivityAssociationHelper */
    private $activityAssociationHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActivityListChainProvider */
    private $activityListChainProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ActivityListFilterHelper */
    private $activityListFilterHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityRoutingHelper */
    private $entityRouterHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryDesignerManager */
    private $queryDesignerManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridHelper */
    private $datagridHelper;

    /** @var ActivityListFilter */
    private $activityListFilter;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->qb = $this->createMock(QueryBuilder::class);
        $this->qb->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->filterUtility = $this->createMock(FilterUtility::class);
        $this->activityAssociationHelper = $this->createMock(ActivityAssociationHelper::class);
        $this->activityListChainProvider = $this->createMock(ActivityListChainProvider::class);
        $this->activityListFilterHelper = $this->createMock(ActivityListFilterHelper::class);
        $this->entityRouterHelper = $this->createMock(EntityRoutingHelper::class);
        $this->queryDesignerManager = $this->createMock(QueryDesignerManager::class);
        $this->datagridHelper = $this->createMock(DatagridHelper::class);

        $this->entityRouterHelper->expects($this->any())
            ->method('resolveEntityClass')
            ->willReturnArgument(0);

        $this->activityListFilter = new ActivityListFilter(
            $this->formFactory,
            $this->filterUtility,
            $this->activityAssociationHelper,
            $this->activityListChainProvider,
            $this->activityListFilterHelper,
            $this->entityRouterHelper,
            $this->queryDesignerManager,
            new ServiceLink($this->datagridHelper)
        );
    }

    public function testApplyShouldThrowExceptionIfWrongDatasourceTypeIsGiven()
    {
        $this->expectException(\LogicException::class);
        $ds = $this->createMock(FilterDatasourceAdapterInterface::class);
        $this->activityListFilter->apply($ds, []);
    }

    public function testApply()
    {
        $ds = $this->createMock(OrmFilterDatasourceAdapter::class);

        $data = [
            'filterType'      => ActivityListFilter::TYPE_HAS_ACTIVITY,
            'entityClassName' => 'entity',
            'activityType'    => [
                'value' => ['c'],
            ],
        ];

        $classMetadata = $this->createMock(ClassMetadata::class);
        $classMetadata->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(['id']);

        $activityQuery = $this->createMock(Query::class);
        $activityQuery->expects($this->once())
            ->method('getDQL')
            ->willReturn('activity dql');

        $activityQb = $this->createMock(QueryBuilder::class);
        $activityQb->expects($this->once())
            ->method('select')
            ->with('1')
            ->willReturn($activityQb);
        $activityQb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->willReturn($activityQb);
        $activityQb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0')
            ->willReturn($activityQb);
        $activityQb->expects($this->once())
            ->method('getQuery')
            ->willReturn($activityQuery);
        $activityQb->expects($this->once())
            ->method('getParameters')
            ->willReturn([]);

        $activityListRepository = $this->createMock(ActivityListRepository::class);
        $activityListRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($activityQb);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with('entity')
            ->willReturn($classMetadata);
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroActivityListBundle:ActivityList')
            ->willReturn($activityListRepository);

        $this->activityAssociationHelper->expects($this->once())
            ->method('hasActivityAssociations')
            ->willReturn(false);

        $expressionBuilder = $this->createMock(OrmExpressionBuilder::class);

        $expressionBuilder->expects($this->once())
            ->method('exists')
            ->with('activity dql')
            ->willReturn($expressionBuilder);

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->willReturn($this->qb);

        $ds->expects($this->once())
            ->method('expr')
            ->willReturn($expressionBuilder);

        $this->activityListFilter->apply($ds, $data);
    }
}
