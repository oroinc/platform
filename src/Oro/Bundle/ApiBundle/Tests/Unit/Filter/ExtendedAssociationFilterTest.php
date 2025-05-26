<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterValueKeyException;
use Oro\Bundle\ApiBundle\Exception\RuntimeException;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Provider\ExtendedAssociationProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendedAssociationFilterTest extends TestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ExtendedAssociationProvider&MockObject $extendedAssociationProvider;
    private EntityOverrideProviderInterface&MockObject $entityOverrideProvider;
    private ExtendedAssociationFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->extendedAssociationProvider = $this->createMock(ExtendedAssociationProvider::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($this->entityOverrideProvider);

        $this->filter = new ExtendedAssociationFilter('integer');
        $this->filter->setValueNormalizer($this->valueNormalizer);
        $this->filter->setExtendedAssociationProvider($this->extendedAssociationProvider);
        $this->filter->setEntityOverrideProviderRegistry($entityOverrideProviderRegistry);
    }

    public function testGetFilterValueName(): void
    {
        self::assertEquals('type', $this->filter->getFilterValueName());
    }

    public function testSearchFilterKey(): void
    {
        $filterValues = [
            'filter[name]'            => new FilterValue('name', 'test'),
            'filter[target.users]'    => new FilterValue('target.users', '123'),
            'filter[target.contacts]' => new FilterValue('target.contacts', '234')
        ];

        $this->filter->setField('target');

        self::assertEquals(
            ['filter[target.users]', 'filter[target.contacts]'],
            $this->filter->searchFilterKeys($filterValues)
        );
    }

    public function testSearchFilterKeyWhenAssociationTargetWasNotSpecified(): void
    {
        $this->expectException(InvalidFilterValueKeyException::class);
        $this->expectExceptionMessage('The target type of an association is not specified.');

        $filterValues = [
            'filter[target]' => new FilterValue('target', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    public function testSearchFilterKeyWhenAssociationTargetIsEmpty(): void
    {
        $this->expectException(InvalidFilterValueKeyException::class);
        $this->expectExceptionMessage('The target type of an association is not specified.');

        $filterValues = [
            'filter[target.]' => new FilterValue('target.', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    public function testSearchFilterKeyWhenAssociationTargetPlaceholderWasNotReplacedWithAssociationType(): void
    {
        $this->expectException(InvalidFilterValueKeyException::class);
        $this->expectExceptionMessage('Replace "type" placeholder with the target type of an association.');

        $filterValues = [
            'filter[target.type]' => new FilterValue('target.type', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    public function testApplyFilter(): void
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));
        $association->setDependsOn(['userField', 'anotherField']);

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with($associationOwnerClass, $associationType, $associationKind, ['userField', 'anotherField'])
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new Comparison('userField', Comparison::EQ, '123'),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterWhenAssociationTargetIsOverridden(): void
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));
        $association->setDependsOn(['userField', 'anotherField']);

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\UserModel');
        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with($associationOwnerClass, $associationType, $associationKind, ['userField', 'anotherField'])
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);
        $this->entityOverrideProvider->expects(self::once())
            ->method('getSubstituteEntityClass')
            ->with('Test\User')
            ->willReturn('Test\UserModel');

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new Comparison('userField', Comparison::EQ, '123'),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterWhenAssociationTargetIsNotSupported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An association with "users" is not supported.');

        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));
        $association->setDependsOn(['userField', 'anotherField']);

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with($associationOwnerClass, $associationType, $associationKind, ['userField', 'anotherField'])
            ->willReturn([]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);
    }

    public function testApplyFilterWhenAllTargetsAreNotAccessibleViaApi(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('An association with "users" is not supported.');

        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);

        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');
        $this->extendedAssociationProvider->expects(self::never())
            ->method('filterExtendedAssociationTargets');

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);
    }

    public function testApplyFilterWithManyToManyAssociation(): void
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToMany';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));
        $association->setDependsOn(['userField', 'anotherField']);

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with($associationOwnerClass, $associationType, $associationKind, ['userField', 'anotherField'])
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new Comparison('userField', 'MEMBER_OF', '123'),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterWithManyToManyAssociationAndNotOperator(): void
    {
        $filterValue = new FilterValue('target.users', '123', FilterOperator::NEQ);
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToMany';
        $associationKind = 'test';

        $config = new EntityDefinitionConfig();
        $association = $config->addField('target');
        $association->setDataType(sprintf('association:%s:%s', $associationType, $associationKind));
        $association->setDependsOn(['userField', 'anotherField']);

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setConfig($config);
        $this->filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->extendedAssociationProvider->expects(self::once())
            ->method('filterExtendedAssociationTargets')
            ->with($associationOwnerClass, $associationType, $associationKind, ['userField', 'anotherField'])
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression('NOT', [new Comparison('userField', 'MEMBER_OF', '123')]),
            $criteria->getWhereExpression()
        );
    }
}
