<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SegmentBundle\Provider\SegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Filter\SegmentFilter;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;

class SegmentFilterTest extends OrmTestCase
{
    const TEST_FIELD_NAME  = 't1.id';
    const TEST_PARAM_VALUE = '%test%';

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var DynamicSegmentQueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $dynamicSegmentQueryBuilder;

    /** @var StaticSegmentQueryBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $staticSegmentQueryBuilder;

    /** @var SegmentProvider|\PHPUnit_Framework_MockObject_MockObject  */
    protected $segmentProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject  */
    protected $configProvider;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject  */
    protected $em;

    /** @var SegmentFilter */
    protected $filter;

    public function setUp()
    {
        $this->formFactory                = $this->getMock('Symfony\Component\Form\FormFactoryInterface');

        $this->dynamicSegmentQueryBuilder = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->staticSegmentQueryBuilder  = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->segmentProvider = $this->getMock('Oro\Bundle\SegmentBundle\Provider\SegmentProvider');

        $this->configProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();

        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new SegmentFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->dynamicSegmentQueryBuilder,
            $this->staticSegmentQueryBuilder,
            $this->segmentProvider,
            $this->configProvider,
            $this->em
        );
    }

    public function tearDown()
    {
        unset($this->formFactory, $this->dynamicSegmentQueryBuilder, $this->filter);
    }

    public function testGetForm()
    {
        $formMock = $this->getMock('Symfony\Component\Form\Test\FormInterface');
        $this->formFactory->expects($this->once())->method('create')
            ->with(
                EntityFilterType::NAME,
                [],
                [
                    'csrf_protection' => false,
                    'field_options'   => [
                        'class'    => 'OroSegmentBundle:Segment',
                        'property' => 'name',
                        'required' => true,
                        'query_builder' => function () {

                        },
                    ]
                ]
            )
            ->will($this->returnValue($formMock));

        $form = $this->filter->getForm();

        // second call should not provoke expectation error, form should be created once
        $this->filter->getForm();
        $this->assertSame($formMock, $form);
    }

    public function testApplyInvalidData()
    {
        $dsMock = $this->getMock('Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface');
        $result = $this->filter->apply($dsMock, [null]);

        $this->assertFalse($result);
    }

    public function testDynamicApply()
    {
        $dynamicSegmentStub = new Segment();
        $dynamicSegmentStub->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $filterData = ['value' => $dynamicSegmentStub];
        $subquery   = 'SELECT ts1.id FROM OroSegmentBundle:CmsUser ts1 WHERE ts1.name LIKE :param1';

        $em = $this->getTestEntityManager();
        $qb = new QueryBuilder($em);
        $qb->select(['t1.name'])
            ->from('OroSegmentBundle:CmsUser', 't1');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $query = new Query($em);
        $query->setDQL($subquery);
        $query->setParameter('param1', self::TEST_PARAM_VALUE);

        $this->dynamicSegmentQueryBuilder->expects($this->once())->method('build')
            ->with($dynamicSegmentStub)
            ->will($this->returnValue($query));

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1',
            'WHERE t1.id IN(SELECT ts1.id FROM OroSegmentBundle:CmsUser ts1 WHERE ts1.name LIKE :param1)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        $this->assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();
        $this->assertCount(1, $params, 'Should pass params to main query builder');
        $this->assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }

    public function testStaticApply()
    {
        $staticSegmentStub = new Segment();
        $staticSegmentStub->setType(new SegmentType(SegmentType::TYPE_STATIC));

        $filterData = ['value' => $staticSegmentStub];
        $subquery   = 'SELECT ts1.entity_id FROM OroSegmentBundle:SegmentSnapshot ts1 WHERE ts1.segmentId = :segment';

        $em = $this->getTestEntityManager();
        $qb = new QueryBuilder($em);
        $qb->select(['t1.name'])
            ->from('OroSegmentBundle:CmsUser', 't1');

        $ds = new OrmFilterDatasourceAdapter($qb);

        $query = new Query($em);
        $query->setDQL($subquery);
        $query->setParameter('segment', $staticSegmentStub);

        $this->staticSegmentQueryBuilder->expects($this->once())->method('build')
            ->with($staticSegmentStub)
            ->will($this->returnValue($query));

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1 WHERE t1.id',
            'IN(SELECT ts1.entity_id FROM OroSegmentBundle:SegmentSnapshot ts1 WHERE ts1.segmentId = :segment)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        $this->assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();
        $this->assertCount(1, $params, 'Should pass params to main query builder');
        $this->assertEquals($staticSegmentStub, $params[0]->getValue());
    }
}
