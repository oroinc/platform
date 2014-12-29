<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Filter;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Filter\SegmentFilter;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\OrmTestCase;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;

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

    /** @var EntityNameProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityNameProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $extendConfigProvider;

    /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var SegmentFilter */
    protected $filter;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()->getMock();

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));

        $registry = $this->getMockForAbstractClass('Doctrine\Common\Persistence\ManagerRegistry', [], '', false);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                [
                    new PreloadedExtension(
                        [
                            'oro_type_filter'        => new FilterType($translator),
                            'oro_type_choice_filter' => new ChoiceFilterType($translator),
                            'entity'                 => new EntityType($registry),
                            'oro_type_entity_filter' => new EntityFilterType($translator),
                        ],
                        []
                    ),
                    new CsrfExtension(
                        $this->getMock('Symfony\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface')
                    )
                ]
            )
            ->getFormFactory();

        $classMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetaData->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('OroSegment:Segment'));
        $classMetaData->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($classMetaData));

        $this->dynamicSegmentQueryBuilder = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->staticSegmentQueryBuilder = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->entityNameProvider   = $this->getMock('Oro\Bundle\SegmentBundle\Provider\EntityNameProvider');
        $this->entityNameProvider
            ->expects($this->any())
            ->method('getEntityName')
            ->will($this->returnValue('Namespace\Entity'));

        $this->entityConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();

        $this->extendConfigProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()->getMock();

        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigManager')
            ->will($this->returnValue($configManager));
        $configManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($this->em));

        $staticQBServiceID  = uniqid('static');
        $dynamicQBServiceID = uniqid('dynamic');
        $container          = new Container();
        $container->set($staticQBServiceID, $this->staticSegmentQueryBuilder);
        $container->set($dynamicQBServiceID, $this->dynamicSegmentQueryBuilder);

        $this->filter = new SegmentFilter(
            $this->formFactory,
            new FilterUtility(),
            new ServiceLink($container, $dynamicQBServiceID),
            new ServiceLink($container, $staticQBServiceID),
            $this->entityNameProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider
        );
        $this->filter->init('segment', ['entity' => '']);
    }

    protected function tearDown()
    {
        unset($this->formFactory, $this->dynamicSegmentQueryBuilder, $this->filter);
    }

    public function testGetMetadata()
    {
        $activeClassName  = 'Oro\Bundle\SegmentBundle\Entity\Segment';
        $newClassName     = 'Test\NewEntity';
        $deletedClassName = 'Test\DeletedEntity';
        $entityConfigIds  = [
            new EntityConfigId('entity', $activeClassName),
            new EntityConfigId('entity', $newClassName),
            new EntityConfigId('entity', $deletedClassName),
        ];

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->will($this->returnValue($entityConfigIds));
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->will(
                $this->returnValueMap(
                    [
                        [
                            $activeClassName,
                            null,
                            $this->createExtendConfig($activeClassName, ExtendScope::STATE_ACTIVE)
                        ],
                        [
                            $newClassName,
                            null,
                            $this->createExtendConfig($newClassName, ExtendScope::STATE_NEW)
                        ],
                        [
                            $deletedClassName,
                            null,
                            $this->createExtendConfig($deletedClassName, ExtendScope::STATE_DELETE)
                        ],
                    ]
                )
            );

        $this->prepareRepo();
        $metadata = $this->filter->getMetadata();

        $this->assertTrue(isset($metadata['entity_ids']));
        $this->assertEquals(
            [$activeClassName => 'id'],
            $metadata['entity_ids']
        );
    }

    /**
     * @param string $className
     * @param string $state
     *
     * @return Config
     */
    protected function createExtendConfig($className, $state)
    {
        $configId = new EntityConfigId('extend', $className);
        $config   = new Config($configId);
        $config->set('state', $state);

        return $config;
    }

    protected function prepareRepo()
    {
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMockForAbstractClass();

        $query->expects($this->once())
            ->method('execute')
            ->will($this->returnValue([]));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->will($this->returnValue($qb));

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroSegmentBundle:Segment'))
            ->will($this->returnValue($repo));
    }

    public function testGetForm()
    {
        $this->prepareRepo();
        $form = $this->filter->getForm();
        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
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
