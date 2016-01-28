<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Filter;

use Oro\Bundle\ActivityListBundle\Filter\ActivityListFilter;

class ActivityListFilterTest extends \PHPUnit_Framework_TestCase
{
    protected $em;
    protected $qb;

    protected $formFactory;
    protected $filterUtility;
    protected $activityAssociationHelper;
    protected $activityListChaingProvider;
    protected $activityListFilterHelper;
    protected $entityRouterHelper;
    protected $queryDesignerManager;
    protected $datagridHelper;

    private $activityListFilter;

    public function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $this->qb->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        $this->formFactory = $this->getMock('Symfony\Component\Form\FormFactoryInterface');
        $this->filterUtility = $this->getMockBuilder('Oro\Bundle\FilterBundle\Filter\FilterUtility')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityAssociationHelper = $this
            ->getMockBuilder('Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->activityListChaingProvider =
            $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
                ->disableOriginalConstructor()
                ->getMock();
        $this->activityListFilterHelper =
            $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Filter\ActivityListFilterHelper')
                ->disableOriginalConstructor()
                ->getMock();
        $this->entityRouterHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityRouterHelper->expects($this->any())
            ->method('decodeClassName')
            ->will($this->returnCallback(function ($className) {
                return $className;
            }));
        $this->queryDesignerManager = $this->getMockBuilder('Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->datagridHelper = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Filter\DatagridHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->activityListFilter = new ActivityListFilter(
            $this->formFactory,
            $this->filterUtility,
            $this->activityAssociationHelper,
            $this->activityListChaingProvider,
            $this->activityListFilterHelper,
            $this->entityRouterHelper,
            $this->queryDesignerManager,
            $this->datagridHelper
        );
    }

    /**
     * @expectedException LogicException
     */
    public function testApplyShouldThrowExceptionIfWrongDatasourceTypeIsGiven()
    {
        $ds = $this->getMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $this->activityListFilter->apply($ds, []);
    }

    public function testApply()
    {
        $ds = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter')
            ->disableOriginalConstructor()
            ->getMock();

        $data = [
            'filterType'      => ActivityListFilter::TYPE_HAS_ACTIVITY,
            'entityClassName' => 'entity',
            'activityType'    => [
                'value' => ['c'],
            ],
        ];

        $classMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetadata->expects($this->once())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $activityQuery = $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Tests\Unit\Stub\Query')
            ->disableOriginalConstructor()
            ->getMock();
        $activityQuery->expects($this->once())
            ->method('getDQL')
            ->will($this->returnValue('activity dql'));

        $activityQb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $activityQb->expects($this->once())
            ->method('select')
            ->with('1')
            ->will($this->returnValue($activityQb));
        $activityQb->expects($this->once())
            ->method('setMaxResults')
            ->with(1)
            ->will($this->returnValue($activityQb));
        $activityQb->expects($this->once())
            ->method('andWhere')
            ->with('1 = 0')
            ->will($this->returnValue($activityQb));
        $activityQb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($activityQuery));
        $activityQb->expects($this->once())
            ->method('getParameters')
            ->will($this->returnValue([]));

        $activityListRepository =
            $this->getMockBuilder('Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository')
                ->disableOriginalConstructor()
                ->getMock();
        $activityListRepository->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($activityQb));

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with('entity')
            ->will($this->returnValue($classMetadata));
        $this->em->expects($this->once())
            ->method('getRepository')
            ->with('OroActivityListBundle:ActivityList')
            ->will($this->returnValue($activityListRepository));

        $this->activityAssociationHelper->expects($this->once())
            ->method('hasActivityAssociations')
            ->will($this->returnValue(false));

        $expressionBuilder = $this->getMockBuilder('Oro\Bundle\FilterBundle\Datasource\Orm\OrmExpressionBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $expressionBuilder->expects($this->once())
            ->method('exists')
            ->with('activity dql')
            ->will($this->returnValue($expressionBuilder));

        $ds->expects($this->any())
            ->method('getQueryBuilder')
            ->will($this->returnValue($this->qb));

        $ds->expects($this->once())
            ->method('expr')
            ->will($this->returnValue($expressionBuilder));

        $this->activityListFilter->apply($ds, $data);
    }
}
