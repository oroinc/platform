<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Filter;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FilterBundle\Datasource\FilterDatasourceAdapterInterface;
use Oro\Bundle\FilterBundle\Datasource\Orm\OrmFilterDatasourceAdapter;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EntityFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SubQueryLimitHelper;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Manager\SegmentManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Filter\SegmentFilter;
use Oro\Bundle\SegmentBundle\Provider\EntityNameProvider;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryBuilderRegistry;
use Oro\Bundle\SegmentBundle\Query\StaticSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Tests\Unit\Stub\Entity\CmsUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Oro\Component\TestUtils\ORM\OrmTestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SegmentFilterTest extends OrmTestCase
{
    use EntityTrait;

    const TEST_FIELD_NAME = 't1.id';
    const TEST_PARAM_VALUE = '%test%';

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormFactoryInterface */
    protected $formFactory;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $doctrine;

    /** @var DynamicSegmentQueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $dynamicSegmentQueryBuilder;

    /** @var StaticSegmentQueryBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $staticSegmentQueryBuilder;

    /** @var EntityNameProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityNameProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $entityConfigProvider;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $extendConfigProvider;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    protected $em;

    /** @var SegmentFilter */
    protected $filter;

    /** @var SubQueryLimitHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $subqueryLimitHelper;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->em);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions(
                [
                    new PreloadedExtension(
                        [
                            'oro_type_filter' => new FilterType($translator),
                            'oro_type_choice_filter' => new ChoiceFilterType($translator),
                            'entity' => new EntityType($this->doctrine),
                            'oro_type_entity_filter' => new EntityFilterType($translator),
                        ],
                        []
                    ),
                    new CsrfExtension(
                        $this->createMock(CsrfTokenManagerInterface::class)
                    )
                ]
            )
            ->getFormFactory();

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->willReturn($this->getClassMetadata());

        $this->dynamicSegmentQueryBuilder = $this->createMock(DynamicSegmentQueryBuilder::class);
        $this->staticSegmentQueryBuilder = $this->createMock(StaticSegmentQueryBuilder::class);

        $this->entityNameProvider = $this->createMock(EntityNameProvider::class);
        $this->entityNameProvider
            ->expects($this->any())
            ->method('getEntityName')
            ->willReturn('Namespace\Entity');

        $this->entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->extendConfigProvider = $this->createMock(ConfigProvider::class);

        $configManager = $this->createMock(ConfigManager::class);
        $this->entityConfigProvider->expects($this->any())
            ->method('getConfigManager')
            ->willReturn($configManager);
        $configManager->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($this->em);

        $segmentQueryBuilderRegistry = new SegmentQueryBuilderRegistry();
        $segmentQueryBuilderRegistry->addQueryBuilder('static', $this->staticSegmentQueryBuilder);
        $segmentQueryBuilderRegistry->addQueryBuilder('dynamic', $this->dynamicSegmentQueryBuilder);
        $this->subqueryLimitHelper = $this->createMock(SubQueryLimitHelper::class);

        $segmentManager = new SegmentManager(
            $this->em,
            $segmentQueryBuilderRegistry,
            $this->subqueryLimitHelper,
            new ArrayCache(),
            $this->createMock(AclHelper::class)
        );

        $this->filter = new SegmentFilter(
            $this->formFactory,
            new FilterUtility(),
            $this->doctrine,
            $segmentManager,
            $this->entityNameProvider,
            $this->entityConfigProvider,
            $this->extendConfigProvider
        );
        $this->filter->init('segment', ['entity' => '']);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function getClassMetadata()
    {
        $classMetaData = $this->createMock(ClassMetadata::class);
        $classMetaData->expects($this->any())
            ->method('getName')
            ->willReturn(Segment::class);
        $classMetaData->expects($this->any())
            ->method('getIdentifier')
            ->willReturn(['id']);
        $classMetaData->expects($this->any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);
        $classMetaData->expects($this->any())
            ->method('getSingleIdentifierFieldName')
            ->willReturn('id');
        $classMetaData->expects($this->any())
            ->method('getTypeOfField')
            ->willReturn('integer');

        return $classMetaData;
    }

    protected function tearDown()
    {
        unset($this->formFactory, $this->dynamicSegmentQueryBuilder, $this->filter);
    }

    public function testGetMetadata()
    {
        $activeClassName = Segment::class;
        $newClassName = 'Test\NewEntity';
        $deletedClassName = 'Test\DeletedEntity';
        $entityConfigIds = [
            new EntityConfigId('entity', $activeClassName),
            new EntityConfigId('entity', $newClassName),
            new EntityConfigId('entity', $deletedClassName),
        ];

        $this->entityConfigProvider->expects($this->once())
            ->method('getIds')
            ->willReturn($entityConfigIds);
        $this->extendConfigProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnMap([
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
            ]);

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
        $config = new Config($configId);
        $config->set('state', $state);

        return $config;
    }

    protected function prepareRepo()
    {
        $query = $this->getMockBuilder(AbstractQuery::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', 'getSQL'])
            ->getMockForAbstractClass();

        $query->expects($this->any())
            ->method('execute')
            ->willReturn([]);
        $query->expects($this->any())
            ->method('getSQL')
            ->willReturn('SQL QUERY');

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())
            ->method('where')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->willReturnSelf();
        $qb->expects($this->any())
            ->method('getParameters')
            ->willReturn(new ArrayCollection());
        $qb->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);

        $this->em->expects($this->any())
            ->method('getRepository')
            ->with($this->equalTo('OroSegmentBundle:Segment'))
            ->willReturn($repo);
    }

    public function testGetForm()
    {
        $this->prepareRepo();
        $form = $this->filter->getForm();
        $this->assertInstanceOf(FormInterface::class, $form);
    }

    public function testApplyInvalidData()
    {
        $dsMock = $this->createMock(FilterDatasourceAdapterInterface::class);
        $result = $this->filter->apply($dsMock, [null]);

        $this->assertFalse($result);
    }

    public function testStaticApply()
    {
        $staticSegmentStub = $this->getEntity(Segment::class, ['id' => 1]);
        $staticSegmentStub->setType(new SegmentType(SegmentType::TYPE_STATIC));
        $staticSegmentStub->setEntity(CmsUser::class);

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
            ->willReturn($queryBuilder);

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1 WHERE',
            't1.id IN(SELECT ts1.id FROM OroSegmentBundle:SegmentSnapshot ts1 WHERE ts1.segmentId = :_s1_segment)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        static::assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();

        static::assertCount(1, $params, 'Should pass params to main query builder');
        static::assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }

    /**
     * @return \Oro\Component\TestUtils\ORM\Mocks\EntityManagerMock
     */
    protected function getEM()
    {
        $reader = new AnnotationReader();
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

    public function testDynamicApplyWithoutLimit()
    {
        $dynamicSegment = $this->getEntity(Segment::class, ['id' => 1]);
        $dynamicSegment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC))
            ->setEntity(CmsUser::class);

        $filterData = ['value' => $dynamicSegment];

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

        $this->dynamicSegmentQueryBuilder
            ->expects(static::once())
            ->method('getQueryBuilder')
            ->with($dynamicSegment)
            ->willReturn($queryBuilder);

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1 WHERE',
            't1.id IN(SELECT ts1.id FROM OroSegmentBundle:SegmentSnapshot ts1 WHERE ts1.segmentId = :_s1_segment)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        static::assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();

        static::assertCount(1, $params, 'Should pass params to main query builder');
        static::assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }

    public function testDynamicApplyWithLimit()
    {
        $dynamicSegment = $this->getEntity(Segment::class, ['id' => 1]);
        $dynamicSegment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC))
            ->setEntity(CmsUser::class)
            ->setRecordsLimit(10);

        $filterData = ['value' => $dynamicSegment];

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

        $this->dynamicSegmentQueryBuilder
            ->expects(static::once())
            ->method('getQueryBuilder')
            ->with($dynamicSegment)
            ->willReturn($queryBuilder);

        $this->subqueryLimitHelper->expects($this->once())
            ->method('setLimit')
            ->with($queryBuilder, 10, 'id')
            ->willReturn($queryBuilder);

        $this->filter->init('someName', [FilterUtility::DATA_NAME_KEY => self::TEST_FIELD_NAME]);
        $this->filter->apply($ds, $filterData);

        $expectedResult = [
            'SELECT t1.name FROM OroSegmentBundle:CmsUser t1 WHERE',
            't1.id IN(SELECT ts1.id FROM OroSegmentBundle:SegmentSnapshot ts1 WHERE ts1.segmentId = :_s1_segment)'
        ];
        $expectedResult = implode(' ', $expectedResult);

        static::assertEquals($expectedResult, $ds->getQueryBuilder()->getDQL());

        $params = $ds->getQueryBuilder()->getParameters();

        static::assertCount(1, $params, 'Should pass params to main query builder');
        static::assertEquals(self::TEST_PARAM_VALUE, $params[0]->getValue());
    }
}
