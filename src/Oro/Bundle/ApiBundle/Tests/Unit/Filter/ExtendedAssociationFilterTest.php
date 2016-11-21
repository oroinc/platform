<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;

use Oro\Bundle\ApiBundle\Filter\ExtendedAssociationFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityExtendBundle\Entity\Manager\AssociationManager;

class ExtendedAssociationFilterTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $associationManager;

    /** @var ExtendedAssociationFilter */
    protected $filter;

    protected function setUp()
    {
        $this->valueNormalizer = $this->getMockBuilder(ValueNormalizer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->associationManager = $this->getMockBuilder(AssociationManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->filter = new ExtendedAssociationFilter('integer');
        $this->filter->setValueNormalizer($this->valueNormalizer);
        $this->filter->setAssociationManager($this->associationManager);
    }

    public function testGetFilterValueName()
    {
        self::assertEquals('type', $this->filter->getFilterValueName());
    }

    public function testSearchFilterKey()
    {
        $filterValues = [
            'filter[name]'         => new FilterValue('name', 'test'),
            'filter[target.users]' => new FilterValue('target.users', '123')
        ];

        $this->filter->setField('target');

        self::assertEquals(
            'filter[target.users]',
            $this->filter->searchFilterKey($filterValues)
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

        $this->filter->searchFilterKey($filterValues);
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

        $this->filter->searchFilterKey($filterValues);
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

        $this->filter->searchFilterKey($filterValues);
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

        $this->assertEquals(
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
}
