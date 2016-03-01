<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

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
    const TEST_FIELD_NAME = 't1.id';
    const TEST_PARAM_VALUE = '%test%';

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

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

        $this->doctrine = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($this->em));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnValue($this->getClassMetadata()));

        $this->dynamicSegmentQueryBuilder = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->staticSegmentQueryBuilder = $this
            ->getMockBuilder('Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder')
            ->disableOriginalConstructor()->getMock();

        $this->entityNameProvider = $this->getMock('Oro\Bundle\SegmentBundle\Provider\EntityNameProvider');
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
            $this->doctrine,
            new ServiceLink($container, $dynamicQBServiceID),
            new ServiceLink($container, $staticQBServiceID),
            $this->entityNameProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider
        );
        $this->filter->init('segment', ['entity' => '']);
    }

    protected function getClassMetadata()
    {
        $classMetaData = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $classMetaData->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('OroSegment:Segment'));
        $classMetaData->expects($this->any())
            ->method('getIdentifier')
            ->will($this->returnValue(['id']));
        $classMetaData->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->will($this->returnValue(array('id')));
        $classMetaData->expects($this->any())
            ->method('getTypeOfField')
            ->will($this->returnValue('integer'));

        return $classMetaData;
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
            ->setMethods(['execute', 'getSQL'])
            ->getMockForAbstractClass();

        $query->expects($this->any())
            ->method('execute')
            ->will($this->returnValue([]));
        $query->expects($this->any())
            ->method('getSQL')
            ->will($this->returnValue('SQL QUERY'));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->once())
            ->method('where')
            ->will($this->returnSelf());
        $qb->expects($this->once())
            ->method('setParameter')
            ->will($this->returnSelf());
        $qb->expects($this->any())
            ->method('getParameters')
            ->will($this->returnValue(new ArrayCollection()));
        $qb->expects($this->any())
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
        $dynamicSegmentStub->setEntity('Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\CmsUser');

        $filterData = ['value' => $dynamicSegmentStub];
        $em         = $this->getEM();

        $qb = $em->createQueryBuilder()
            ->select(['t1.name'])
            ->from('OroSegmentBundle:CmsUser', 't1');

        $queryBuilder = new QueryBuilder($em);
        $queryBuilder->select(['ts1.id'])
            ->from('OroSegmentBundle:CmsUser', 'ts1')
            ->andWhere('ts1.name LIKE :param1')
            ->setParameter('param1', self::TEST_PARAM_VALUE);

        $ds = new OrmFilterDatasourceAdapter($qb);

        $this->dynamicSegmentQueryBuilder
            ->expects(static::once())
            ->method('getQueryBuilder')
            ->with($dynamicSegmentStub)
            ->will(static::returnValue($queryBuilder));

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1',
            'WHERE EXISTS(SELECT ts1.id FROM OroSegmentBundle:CmsUser ts1' .
            ' WHERE ts1.name LIKE :param1 AND ts1.id = t1.id)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        static::assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();
        static::assertCount(1, $params, 'Should pass params to main query builder');
        static::assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }

    public function testStaticApply()
    {
        $staticSegmentStub = new Segment();
        $staticSegmentStub->setType(new SegmentType(SegmentType::TYPE_STATIC));
        $staticSegmentStub->setEntity('Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\CmsUser');

        $filterData = ['value' => $staticSegmentStub];

        $em = $this->getEM();
        $qb = $em->createQueryBuilder()
            ->select(['t1.name'])
            ->from('OroSegmentBundle:CmsUser', 't1');

        $queryBuilder = new QueryBuilder($em);
        $queryBuilder->select(['ts1.id'])
            ->from('OroSegmentBundle:SegmentSnapshot', 'ts1')
            ->andWhere('ts1.segmentId = :segment')
            ->setParameter('segment', self::TEST_PARAM_VALUE);

        $ds = new OrmFilterDatasourceAdapter($qb);

        $this->staticSegmentQueryBuilder
            ->expects(static::once())
            ->method('getQueryBuilder')
            ->with($staticSegmentStub)
            ->will(static::returnValue($queryBuilder));

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1 WHERE',
            'EXISTS(SELECT ts1.id FROM OroSegmentBundle:SegmentSnapshot ts1 ' .
            'WHERE ts1.segmentId = :segment AND ts1.integerEntityId = t1.id)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        static::assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();

        static::assertCount(1, $params, 'Should pass params to main query builder');
        static::assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }

    /**
     * @return \Oro\Bundle\TestFrameworkBundle\Test\Doctrine\ORM\Mocks\EntityManagerMock
     */
    protected function getEM()
    {
        $reader         = new AnnotationReader();
        $metadataDriver = new AnnotationDriver(
            $reader,
            'Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity'
        );

        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setMetadataDriverImpl($metadataDriver);
        $em->getConfiguration()->setEntityNamespaces(
            [
                'OroSegmentBundle' => 'Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity'
            ]
        );

        return $em;
    }
}
