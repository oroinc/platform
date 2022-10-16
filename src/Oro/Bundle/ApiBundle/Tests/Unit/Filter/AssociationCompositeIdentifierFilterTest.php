<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Oro\Bundle\ApiBundle\Filter\AssociationCompositeIdentifierFilter;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;

class AssociationCompositeIdentifierFilterTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityIdTransformerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $idTransformerRegistry;

    /** @var AssociationCompositeIdentifierFilter */
    private $filter;

    protected function setUp(): void
    {
        $this->idTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->filter = new AssociationCompositeIdentifierFilter(DataType::STRING);
        $this->filter->setEntityIdTransformerRegistry($this->idTransformerRegistry);
        $this->filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $this->filter->setField('testField');
    }

    public function testApplyFilterForEqualOperatorAndOneId(): void
    {
        $filterValue = new FilterValue('id', 'id1=1;renamedId2=2');
        $requestType = new RequestType([RequestType::REST]);
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->filter->setRequestType($requestType);
        $this->filter->setMetadata($metadata);

        $this->idTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('id1=1;renamedId2=2', self::identicalTo($metadata))
            ->willReturn(['id1' => 1, 'renamedId2' => 2]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new Comparison('testField.id1', Comparison::EQ, 1),
                    new Comparison('testField.id2', Comparison::EQ, 2)
                ]
            ),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterForNotEqualOperatorAndOneId(): void
    {
        $filterValue = new FilterValue('id', 'id1=1;renamedId2=2', FilterOperator::NEQ);
        $requestType = new RequestType([RequestType::REST]);
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->filter->setRequestType($requestType);
        $this->filter->setMetadata($metadata);

        $this->idTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('id1=1;renamedId2=2', self::identicalTo($metadata))
            ->willReturn(['id1' => 1, 'renamedId2' => 2]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new Comparison('testField.id1', Comparison::NEQ, 1),
                    new Comparison('testField.id2', Comparison::NEQ, 2)
                ]
            ),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterForEqualOperatorAndSeveralIds(): void
    {
        $filterValue = new FilterValue('id', ['id1=1;renamedId2=2', 'id1=3;renamedId2=4']);
        $requestType = new RequestType([RequestType::REST]);
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->filter->setRequestType($requestType);
        $this->filter->setMetadata($metadata);

        $this->idTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['id1=1;renamedId2=2', $metadata, ['id1' => 1, 'renamedId2' => 2]],
                ['id1=3;renamedId2=4', $metadata, ['id1' => 3, 'renamedId2' => 4]]
            ]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_OR,
                [
                    new CompositeExpression(
                        CompositeExpression::TYPE_AND,
                        [
                            new Comparison('testField.id1', Comparison::EQ, 1),
                            new Comparison('testField.id2', Comparison::EQ, 2)
                        ]
                    ),
                    new CompositeExpression(
                        CompositeExpression::TYPE_AND,
                        [
                            new Comparison('testField.id1', Comparison::EQ, 3),
                            new Comparison('testField.id2', Comparison::EQ, 4)
                        ]
                    )
                ]
            ),
            $criteria->getWhereExpression()
        );
    }

    public function testApplyFilterForNotEqualOperatorAndSeveralIds(): void
    {
        $filterValue = new FilterValue('id', ['id1=1;renamedId2=2', 'id1=3;renamedId2=4'], FilterOperator::NEQ);
        $requestType = new RequestType([RequestType::REST]);
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setIdentifierFieldNames(['id1', 'renamedId2']);
        $metadata->addField(new FieldMetadata('id1'));
        $metadata->addField(new FieldMetadata('renamedId2'))->setPropertyPath('id2');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->filter->setRequestType($requestType);
        $this->filter->setMetadata($metadata);

        $this->idTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with(self::identicalTo($requestType))
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['id1=1;renamedId2=2', $metadata, ['id1' => 1, 'renamedId2' => 2]],
                ['id1=3;renamedId2=4', $metadata, ['id1' => 3, 'renamedId2' => 4]]
            ]);

        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals(
            new CompositeExpression(
                CompositeExpression::TYPE_AND,
                [
                    new CompositeExpression(
                        CompositeExpression::TYPE_OR,
                        [
                            new Comparison('testField.id1', Comparison::NEQ, 1),
                            new Comparison('testField.id2', Comparison::NEQ, 2)
                        ]
                    ),
                    new CompositeExpression(
                        CompositeExpression::TYPE_OR,
                        [
                            new Comparison('testField.id1', Comparison::NEQ, 3),
                            new Comparison('testField.id2', Comparison::NEQ, 4)
                        ]
                    )
                ]
            ),
            $criteria->getWhereExpression()
        );
    }
}
