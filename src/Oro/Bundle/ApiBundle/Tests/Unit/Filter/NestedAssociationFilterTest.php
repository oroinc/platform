<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Filter\NestedAssociationFilter;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\TestCase;

class NestedAssociationFilterTest extends TestCase
{
    private NestedAssociationFilter $filter;

    #[\Override]
    protected function setUp(): void
    {
        $config = new EntityDefinitionConfig();
        $targetConfig = $config->addField('someField')->createAndSetTargetEntity();
        $targetConfig->addField('__class__')->setPropertyPath('entityClass');
        $targetConfig->addField('id')->setPropertyPath('entityId');

        $metadata = new EntityMetadata('Test\ParentEntity');
        $targetMetadata = new EntityMetadata('Test\Entity');
        $metadata->addAssociation(new AssociationMetadata('someField'))->setTargetMetadata($targetMetadata);
        $targetMetadata->setIdentifierFieldNames(['id']);
        $targetMetadata->addField(new FieldMetadata('id'))->setPropertyPath('key');

        $requestType = new RequestType([RequestType::REST]);
        $valueNormalizer = $this->createMock(ValueNormalizer::class);
        $valueNormalizer->expects(self::any())
            ->method('normalizeValue')
            ->with('test_entity', DataType::ENTITY_CLASS, $requestType)
            ->willReturn('Test\Entity');

        $this->filter = new NestedAssociationFilter(DataType::INTEGER);
        $this->filter->setSupportedOperators([
            FilterOperator::EQ,
            FilterOperator::NEQ,
            FilterOperator::EXISTS,
            FilterOperator::NEQ_OR_NULL
        ]);
        $this->filter->setField('someField');
        $this->filter->setConfig($config);
        $this->filter->setMetadata($metadata);
        $this->filter->setRequestType($requestType);
        $this->filter->setValueNormalizer($valueNormalizer);
    }

    public function testUnsupportedOperator(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);
        $this->expectExceptionMessage('The operator "contains" is not supported.');

        $this->filter->apply(
            new Criteria(),
            new FilterValue('someField.test_entity', 'value', FilterOperator::CONTAINS)
        );
    }

    public function testUnsupportedOperatorWhenItConfiguredExplicitly(): void
    {
        $this->expectException(InvalidFilterOperatorException::class);
        $this->expectExceptionMessage('The operator "contains" is not supported.');

        $this->filter->setSupportedOperators([FilterOperator::EQ, FilterOperator::CONTAINS]);
        $this->filter->apply(
            new Criteria(),
            new FilterValue('someField.test_entity', 'value', FilterOperator::CONTAINS)
        );
    }

    /**
     * @dataProvider filterDataProvider
     */
    public function testFilter(FilterValue $filterValue, Expression $expectation): void
    {
        $criteria = new Criteria();
        $this->filter->apply($criteria, $filterValue);

        self::assertEquals($expectation, $criteria->getWhereExpression());
    }

    public static function filterDataProvider(): array
    {
        return [
            'EQ filter' => [
                new FilterValue('someField.test_entity', 'value', FilterOperator::EQ),
                new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    [
                        new Comparison('entityClass', Comparison::EQ, 'Test\Entity'),
                        new Comparison('entityId', 'ENTITY', [
                            'Test\Entity',
                            new Comparison('key', Comparison::EQ, 'value')
                        ])
                    ]
                )
            ],
            'NEQ filter' => [
                new FilterValue('someField.test_entity', 'value', FilterOperator::NEQ),
                new CompositeExpression(
                    'NOT',
                    [
                        new CompositeExpression(
                            CompositeExpression::TYPE_AND,
                            [
                                new Comparison('entityClass', Comparison::EQ, 'Test\Entity'),
                                new Comparison('entityId', 'ENTITY', [
                                    'Test\Entity',
                                    new Comparison('key', Comparison::EQ, 'value')
                                ])
                            ]
                        )
                    ]
                )
            ],
            'EXISTS filter' => [
                new FilterValue('someField.test_entity', true, FilterOperator::EXISTS),
                new Comparison('entityClass', Comparison::EQ, 'Test\Entity')
            ],
            'not EXISTS filter' => [
                new FilterValue('someField.test_entity', false, FilterOperator::EXISTS),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            'NOT',
                            [
                                new Comparison('entityClass', Comparison::EQ, 'Test\Entity')
                            ]
                        ),
                        new Comparison('entityId', Comparison::EQ, null)
                    ]
                )
            ],
            'NEQ_OR_NULL filter' => [
                new FilterValue('someField.test_entity', true, FilterOperator::NEQ_OR_NULL),
                new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    [
                        new CompositeExpression(
                            'NOT',
                            [
                                new CompositeExpression(
                                    CompositeExpression::TYPE_AND,
                                    [
                                        new Comparison('entityClass', Comparison::EQ, 'Test\Entity'),
                                        new Comparison('entityId', 'ENTITY', [
                                            'Test\Entity',
                                            new Comparison('key', Comparison::EQ, 'value')
                                        ])
                                    ]
                                )
                            ]
                        ),
                        new Comparison('entityId', Comparison::EQ, null)
                    ]
                )
            ]
        ];
    }
}
