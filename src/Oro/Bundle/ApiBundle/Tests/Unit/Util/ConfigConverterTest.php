<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigConverter;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Component\EntitySerializer\AssociationQuery;

class ConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityOverrideProviderRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $entityOverrideProviderRegistry;

    /** @var ConfigConverter */
    private $configConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->configConverter = new ConfigConverter($this->entityOverrideProviderRegistry);
    }

    public function testConvertConfigWithoutParentResourceClass(): void
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertFalse($convertedConfig->has('skip_acl_for_root_entity'));
    }

    public function testConvertConfigWithSkipAclForRootEntity(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'skip_acl_for_root_entity' => true
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertTrue($convertedConfig->has('skip_acl_for_root_entity'));
        self::assertTrue($convertedConfig->get('skip_acl_for_root_entity'));
    }

    public function testConvertConfigWithResourceClass(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'resource_class' => 'Test\Model'
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertTrue($convertedConfig->has('resource_class'));
        self::assertEquals('Test\Model', $convertedConfig->get('resource_class'));
        self::assertFalse($convertedConfig->has('skip_acl_for_root_entity'));
    }

    public function testConvertConfigWithParentResourceClass(): void
    {
        $config = [
            'exclusion_policy' => 'all',
            'parent_resource_class' => 'Test\Entity'
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertFalse($convertedConfig->has('parent_resource_class'));
        self::assertFalse($convertedConfig->has('skip_acl_for_root_entity'));
    }

    /**
     * @dataProvider convertConfigWithAssociationQueryDataProvider
     */
    public function testConvertConfigWithAssociationQuery(?string $targetType): void
    {
        $requestType = new RequestType(['test']);
        $associationName = 'association1';
        $targetClass = 'Test\TargetClass';
        $qb = $this->createMock(QueryBuilder::class);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                $associationName => [
                    'target_class'      => $targetClass,
                    'association_query' => $qb
                ]
            ]
        ];
        $isCollection = true;
        if ($targetType) {
            $config['fields'][$associationName]['target_type'] = $targetType;
            if ('to-one' === $targetType) {
                $isCollection = false;
            }
        }

        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->entityOverrideProviderRegistry->expects(self::once())
            ->method('getEntityOverrideProvider')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::once())
            ->method('getEntityClass')
            ->with($targetClass)
            ->willReturn(null);

        $this->configConverter->setRequestType($requestType);
        $convertedConfig = $this->configConverter->convertConfig($config);

        /** @var AssociationQuery $associationQuery */
        $associationQuery = $convertedConfig->getField($associationName)->get('association_query');
        self::assertInstanceOf(AssociationQuery::class, $associationQuery);
        self::assertEquals($targetClass, $associationQuery->getTargetEntityClass());
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertSame($isCollection, $associationQuery->isCollection());
    }

    /**
     * @dataProvider convertConfigWithAssociationQueryDataProvider
     */
    public function testConvertConfigWithAssociationQueryForEnum(?string $targetType): void
    {
        $requestType = new RequestType(['test']);
        $associationName = 'association1';
        $targetClass = 'Extend\Entity\EV_Test_Target_Class';
        $qb = $this->createMock(QueryBuilder::class);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                $associationName => [
                    'target_class'      => $targetClass,
                    'association_query' => $qb
                ]
            ]
        ];
        $isCollection = true;
        if ($targetType) {
            $config['fields'][$associationName]['target_type'] = $targetType;
            if ('to-one' === $targetType) {
                $isCollection = false;
            }
        }

        $this->entityOverrideProviderRegistry->expects(self::never())
            ->method('getEntityOverrideProvider');

        $this->configConverter->setRequestType($requestType);
        $convertedConfig = $this->configConverter->convertConfig($config);

        /** @var AssociationQuery $associationQuery */
        $associationQuery = $convertedConfig->getField($associationName)->get('association_query');
        self::assertInstanceOf(AssociationQuery::class, $associationQuery);
        self::assertEquals(EnumOption::class, $associationQuery->getTargetEntityClass());
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertSame($isCollection, $associationQuery->isCollection());
    }

    public function convertConfigWithAssociationQueryDataProvider(): array
    {
        return [
            ['targetType' => null],
            ['targetType' => 'to-many'],
            ['targetType' => 'to-one']
        ];
    }

    public function testConvertConfigWithAssociationQueryAndWhenTargetClassIsModelThatOverridesEntity(): void
    {
        $requestType = new RequestType(['test']);
        $associationName = 'association1';
        $targetClass = 'Test\TargetModelClass';
        $targetEntityClass = 'Test\TargetEntityClass';
        $qb = $this->createMock(QueryBuilder::class);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                $associationName => [
                    'target_class'      => $targetClass,
                    'association_query' => $qb
                ]
            ]
        ];

        $entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);
        $this->entityOverrideProviderRegistry->expects(self::once())
            ->method('getEntityOverrideProvider')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityOverrideProvider);
        $entityOverrideProvider->expects(self::once())
            ->method('getEntityClass')
            ->with($targetClass)
            ->willReturn($targetEntityClass);

        $this->configConverter->setRequestType($requestType);
        $convertedConfig = $this->configConverter->convertConfig($config);

        /** @var AssociationQuery $associationQuery */
        $associationQuery = $convertedConfig->getField($associationName)->get('association_query');
        self::assertInstanceOf(AssociationQuery::class, $associationQuery);
        self::assertEquals($targetEntityClass, $associationQuery->getTargetEntityClass());
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertTrue($associationQuery->isCollection());
    }

    public function testConvertConfigWithAssociationQueryButWithoutRequestType(): void
    {
        $associationName = 'association1';
        $targetClass = 'Test\TargetClass';
        $qb = $this->createMock(QueryBuilder::class);

        $config = [
            'exclusion_policy' => 'all',
            'fields'           => [
                $associationName => [
                    'target_class'      => $targetClass,
                    'association_query' => $qb
                ]
            ]
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        /** @var AssociationQuery $associationQuery */
        $associationQuery = $convertedConfig->getField($associationName)->get('association_query');
        self::assertInstanceOf(AssociationQuery::class, $associationQuery);
        self::assertEquals($targetClass, $associationQuery->getTargetEntityClass());
        self::assertSame($qb, $associationQuery->getQueryBuilder());
        self::assertTrue($associationQuery->isCollection());
    }
}
