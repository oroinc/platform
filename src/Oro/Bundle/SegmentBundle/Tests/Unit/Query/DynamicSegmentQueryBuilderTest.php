<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Query;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Configuration\EntityConfigurationProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ConfigVirtualFieldProvider;
use Oro\Bundle\EntityBundle\Provider\EntityHierarchyProviderInterface;
use Oro\Bundle\FilterBundle\Filter\FilterExecutionContext;
use Oro\Bundle\FilterBundle\Filter\FilterInterface;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Filter\StringFilter;
use Oro\Bundle\FilterBundle\Form\Type\Filter\FilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\RestrictionBuilder;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Query\DynamicSegmentQueryBuilder;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverter;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterFactory;
use Oro\Bundle\SegmentBundle\Query\SegmentQueryConverterState;
use Oro\Bundle\SegmentBundle\Tests\Unit\SegmentDefinitionTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DynamicSegmentQueryBuilderTest extends SegmentDefinitionTestCase
{
    /** @var FormFactoryInterface */
    private $formFactory;

    protected function setUp(): void
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnArgument(0);

        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtensions([
                new PreloadedExtension(
                    [
                        'oro_type_text_filter' => new TextFilterType($translator),
                        'oro_type_filter'      => new FilterType($translator)
                    ],
                    []
                ),
                new CsrfExtension(
                    $this->createMock(CsrfTokenManagerInterface::class)
                )
            ])
            ->getFormFactory();
    }

    public function testBuild()
    {
        $segment = $this->getSegment();
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $doctrine = $this->getDoctrine(
            [self::TEST_ENTITY => ['username' => 'string', 'email' => 'string']],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $builder = $this->getQueryBuilder($doctrine);
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $doctrine->getManagerForClass(self::TEST_ENTITY);
        $qb = new QueryBuilder($em);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $em->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());
        $em->expects($this->any())
            ->method('createQuery')
            ->willReturn(new Query($em));

        $builder->build($segment);

        $result = $qb->getDQL();
        $counter = 0;
        $result = preg_replace_callback(
            '/(:[a-z]+)(\d+)/',
            static function ($matches) use (&$counter) {
                return $matches[1] . (++$counter);
            },
            $result
        );

        $this->assertSame(
            'SELECT t1_0d251dec2c395afb3e7cd2d87ee40bbc_1.userName, t1_0d251dec2c395afb3e7cd2d87ee40bbc_1.id ' .
            'FROM AcmeBundle:UserEntity t1_0d251dec2c395afb3e7cd2d87ee40bbc_1 ' .
            'WHERE t1_0d251dec2c395afb3e7cd2d87ee40bbc_1.email LIKE :_gpnpstring1',
            $result
        );
    }

    public function testBuildExtended()
    {
        $segment = $this->getSegment(
            [
                'columns'          => [
                    [
                        'name'  => 'id',
                        'label' => 'Id'
                    ],
                    [
                        'name'    => 'userName',
                        'label'   => 'User name',
                        'func'    => null,
                        'sorting' => 'ASC'
                    ]
                ],
                'grouping_columns' => [['name' => 'id']],
                'filters'          => [
                    [
                        'columnName' => 'address+AcmeBundle:Address::zip',
                        'criterion'  => [
                            'filter' => 'string',
                            'data'   => [
                                'type'  => 1,
                                'value' => 'zip_code'
                            ]
                        ]
                    ],
                    'AND',
                    [
                        'columnName' => 'status+AcmeBundle:Status::code',
                        'criterion'  => [
                            'filter' => 'string',
                            'data'   => [
                                'type'  => 1,
                                'value' => 'code'
                            ]
                        ]
                    ]
                ]
            ]
        );
        $segment->setType(new SegmentType(SegmentType::TYPE_DYNAMIC));

        $doctrine = $this->getDoctrine(
            [
                self::TEST_ENTITY    => [
                    'username' => 'string',
                    'email'    => 'string',
                    'address'  => ['id'],
                    'status'   => ['id']
                ],
                'AcmeBundle:Address' => ['zip' => 'string'],
                'AcmeBundle:Status'  => ['code' => 'string']
            ],
            [self::TEST_ENTITY => [self::TEST_IDENTIFIER_NAME]]
        );
        $builder = $this->getQueryBuilder($doctrine);
        /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject $em */
        $em = $doctrine->getManagerForClass(self::TEST_ENTITY);
        $qb = new QueryBuilder($em);
        $connection = $this->createMock(Connection::class);
        $connection->expects($this->any())
            ->method('getDatabasePlatform')
            ->willReturn(null);
        $em->expects($this->any())
            ->method('getConnection')
            ->willReturn($connection);
        $em->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $em->expects($this->any())
            ->method('getExpressionBuilder')
            ->willReturn(new Expr());
        $em->expects($this->any())
            ->method('createQuery')
            ->willReturn(new Query($em));

        $builder->build($segment);

        $this->assertEmpty($qb->getDQLPart('groupBy'));
        $this->assertNotEmpty($qb->getDQLPart('orderBy'));
        $this->assertNotEmpty($qb->getDQLPart('join'));
    }

    private function getQueryBuilder(ManagerRegistry $doctrine = null): DynamicSegmentQueryBuilder
    {
        $manager = $this->createMock(Manager::class);
        $manager->expects($this->any())
            ->method('createFilter')
            ->willReturnCallback(function ($name, $params) {
                return $this->createFilter($name, $params);
            });

        $entityHierarchyProvider = $this->createMock(EntityHierarchyProviderInterface::class);
        $entityHierarchyProvider->expects($this->any())
            ->method('getHierarchy')
            ->willReturn([]);

        $entityConfigurationProvider = $this->createMock(EntityConfigurationProvider::class);
        $entityConfigurationProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn([]);
        $virtualFieldProvider = new ConfigVirtualFieldProvider(
            $entityHierarchyProvider,
            $entityConfigurationProvider
        );

        $doctrine = $doctrine ?? $this->getDoctrine();

        $segmentQueryConverterFactory = $this->createMock(SegmentQueryConverterFactory::class);
        $configManager = $this->createMock(ConfigManager::class);

        $filterExecutionContext = new FilterExecutionContext();
        $filterExecutionContext->enableValidation();
        $cache = $this->createMock(CacheItemPoolInterface::class);
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cache->expects($this->any())
            ->method('getItem')
            ->willReturn($cacheItem);
        $cacheItem->expects($this->any())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects($this->any())
            ->method('set')
            ->willReturn($cacheItem);
        $segmentQueryConverterFactory->expects($this->once())
            ->method('createInstance')
            ->willReturn(new SegmentQueryConverter(
                $manager,
                $virtualFieldProvider,
                $this->getVirtualRelationProvider(),
                new DoctrineHelper($doctrine),
                new RestrictionBuilder($manager, $configManager, $filterExecutionContext),
                new SegmentQueryConverterState($cache)
            ));

        return new DynamicSegmentQueryBuilder($segmentQueryConverterFactory, $doctrine);
    }

    /**
     * Creates a new instance of a filter based on a configuration
     * of a filter registered in this manager with the given name
     */
    public function createFilter(string $name, array $params = null): FilterInterface
    {
        $defaultParams = [
            'type' => $name
        ];
        if (!empty($params)) {
            $params = array_merge($defaultParams, $params);
        }

        if ('string' !== $name) {
            throw new \Exception(sprintf('Not implemented in this test filter: "%s" . ', $name));
        }

        $filter = new StringFilter($this->formFactory, new FilterUtility());
        $filter->init($name, $params);

        return $filter;
    }
}
