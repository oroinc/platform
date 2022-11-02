<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\ConfigConverter;
use Oro\Component\EntitySerializer\AssociationQuery;

class ConfigConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderRegistry */
    private $entityOverrideProviderRegistry;

    /** @var ConfigConverter */
    private $configConverter;

    protected function setUp(): void
    {
        $this->entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);

        $this->configConverter = new ConfigConverter($this->entityOverrideProviderRegistry);
    }

    public function testConvertConfigWithoutParentResourceClass()
    {
        $config = [
            'exclusion_policy' => 'all'
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertFalse($convertedConfig->has('skip_acl_for_root_entity'));
    }

    public function testConvertConfigWithParentResourceClass()
    {
        $config = [
            'exclusion_policy'      => 'all',
            'parent_resource_class' => 'Test\Entity'
        ];

        $convertedConfig = $this->configConverter->convertConfig($config);

        self::assertTrue($convertedConfig->has('skip_acl_for_root_entity'));
        self::assertTrue($convertedConfig->get('skip_acl_for_root_entity'));
    }

    /**
     * @dataProvider convertConfigWithAssociationQueryDataProvider
     */
    public function testConvertConfigWithAssociationQuery(?string $targetType)
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

    public function convertConfigWithAssociationQueryDataProvider(): array
    {
        return [
            ['targetType' => null],
            ['targetType' => 'to-many'],
            ['targetType' => 'to-one']
        ];
    }

    public function testConvertConfigWithAssociationQueryAndWhenTargetClassIsModelThatOverridesEntity()
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

    public function testConvertConfigWithAssociationQueryButWithoutRequestType()
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
