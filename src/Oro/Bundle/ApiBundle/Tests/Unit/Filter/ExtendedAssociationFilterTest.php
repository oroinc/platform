<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderInterface;
use Oro\Bundle\ApiBundle\Provider\EntityOverrideProviderRegistry;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ExtendedAssociationFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AssociationManager */
    private $associationManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityOverrideProviderInterface */
    private $entityOverrideProvider;

    /** @var ExtendedAssociationFilter */
    private $filter;

    protected function setUp()
    {
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->associationManager = $this->createMock(AssociationManager::class);
        $this->entityOverrideProvider = $this->createMock(EntityOverrideProviderInterface::class);

        $entityOverrideProviderRegistry = $this->createMock(EntityOverrideProviderRegistry::class);
        $entityOverrideProviderRegistry->expects(self::any())
            ->method('getEntityOverrideProvider')
            ->willReturn($this->entityOverrideProvider);

        $this->filter = new ExtendedAssociationFilter('integer');
        $this->filter->setValueNormalizer($this->valueNormalizer);
        $this->filter->setAssociationManager($this->associationManager);
        $this->filter->setEntityOverrideProviderRegistry($entityOverrideProviderRegistry);
    }

    public function testGetFilterValueName()
    {
        self::assertEquals('type', $this->filter->getFilterValueName());
    }

    public function testSearchFilterKey()
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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException
     * @expectedExceptionMessage The target type of an association is not specified.
     */
    public function testSearchFilterKeyWhenAssociationTargetWasNotSpecified()
    {
        $filterValues = [
            'filter[target]' => new FilterValue('target', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException
     * @expectedExceptionMessage The target type of an association is not specified.
     */
    public function testSearchFilterKeyWhenAssociationTargetIsEmpty()
    {
        $filterValues = [
            'filter[target.]' => new FilterValue('target.', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Filter\InvalidFilterValueKeyException
     * @expectedExceptionMessage Replace "type" placeholder with the target type of an association.
     */
    public function testSearchFilterKeyWhenAssociationTargetPlaceholderWasNotReplacedWithAssociationType()
    {
        $filterValues = [
            'filter[target.type]' => new FilterValue('target.type', '123')
        ];

        $this->filter->setField('target');

        $this->filter->searchFilterKeys($filterValues);
    }

    public function testApplyFilter()
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($associationOwnerClass, null, $associationType, $associationKind)
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new Comparison('userField', Comparison::EQ, '123'),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterWhenAssociationTargetIsOverridden()
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\UserModel');
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($associationOwnerClass, null, $associationType, $associationKind)
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

    /**
     * @expectedException \Oro\Bundle\ApiBundle\Exception\RuntimeException
     * @expectedExceptionMessage An association with "users" is not supported.
     */
    public function testApplyFilterWhenAssociationTargetIsNotSupported()
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToOne';
        $associationKind = 'test';

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($associationOwnerClass, null, $associationType, $associationKind)
            ->willReturn([]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);
    }

    public function testApplyFilterWithManyToManyAssociation()
    {
        $filterValue = new FilterValue('target.users', '123');
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToMany';
        $associationKind = 'test';

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($associationOwnerClass, null, $associationType, $associationKind)
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new Comparison('userField', 'MEMBER_OF', '123'),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterWithManyToManyAssociationAndNotOperator()
    {
        $filterValue = new FilterValue('target.users', '123', ComparisonFilter::NEQ);
        $requestType = new RequestType([RequestType::REST]);
        $associationOwnerClass = 'Test\OwnerClass';
        $associationType = 'manyToMany';
        $associationKind = 'test';

        $this->filter->setField('target');
        $this->filter->setRequestType($requestType);
        $this->filter->setAssociationOwnerClass($associationOwnerClass);
        $this->filter->setAssociationType($associationType);
        $this->filter->setAssociationKind($associationKind);
        $this->filter->setSupportedOperators([ComparisonFilter::EQ, ComparisonFilter::NEQ]);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('users', DataType::ENTITY_CLASS, self::identicalTo($requestType))
            ->willReturn('Test\User');
        $this->associationManager->expects(self::once())
            ->method('getAssociationTargets')
            ->with($associationOwnerClass, null, $associationType, $associationKind)
            ->willReturn(['Test\User' => 'userField', 'Test\Another' => 'anotherField']);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression('NOT', [new Comparison('userField', 'MEMBER_OF', '123')]),
            $criteria->getWhereExpression()
        );
    }
}
