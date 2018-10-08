<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Shared;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterValue;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\ErrorSource;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Processor\Shared\NormalizeFilterValues;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Filter\TestFilterValueAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetList\GetListProcessorTestCase;

class NormalizeFilterValuesTest extends GetListProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerRegistry */
    private $entityIdTransformerRegistry;

    /** @var NormalizeFilterValues */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);

        $this->processor = new NormalizeFilterValues(
            $this->valueNormalizer,
            $this->entityIdTransformerRegistry
        );
    }

    public function testProcessOnExistingQuery()
    {
        $this->context->setQuery(new \stdClass());
        $context = clone $this->context;
        $this->processor->process($this->context);
        self::assertEquals($context, $this->context);
    }

    public function testProcessForFieldFilters()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $stringFilter = new ComparisonFilter('string');
        $filters->add('id', $integerFilter);
        $filters->add('label', $stringFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', '1'));
        $filterValues->set('label', new FilterValue('label', 'test'));

        $this->valueNormalizer->expects(self::exactly(2))
            ->method('normalizeValue')
            ->willReturnMap(
                [
                    ['1', 'integer', $this->context->getRequestType(), false, false, 1],
                    ['test', 'string', $this->context->getRequestType(), false, false, 'test']
                ]
            );
        $this->entityIdTransformerRegistry->expects(self::never())
            ->method('getEntityIdTransformer');

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertSame(1, $filterValues->get('id')->getValue());
        self::assertSame('test', $filterValues->get('label')->getValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForSingleIdFilter()
    {
        $filters = $this->context->getFilters();
        $idFilter = new ComparisonFilter('integer');
        $idFilter->setField('idField');
        $filters->add('id', $idFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', 'predefinedId'));

        $metadata = new EntityMetadata();
        $metadata->setIdentifierFieldNames(['id']);
        $idField = new FieldMetadata('id');
        $idField->setPropertyPath('idField');
        $metadata->addField($idField);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('predefinedId', 'string', $this->context->getRequestType(), false, false)
            ->willReturn('predefinedId');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('predefinedId', self::identicalTo($metadata))
            ->willReturn(1);

        $this->context->setFilterValues($filterValues);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame(1, $filterValues->get('id')->getValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForAssociationFilter()
    {
        $filters = $this->context->getFilters();
        $associationFilter = new ComparisonFilter('integer');
        $associationFilter->setField('associationField');
        $filters->add('association', $associationFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('association', new FilterValue('association', 'predefinedId'));

        $metadata = new EntityMetadata();
        $associationMetadata = new AssociationMetadata('associationField');
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName('AssociationTargetClass');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $metadata->addAssociation($associationMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('predefinedId', 'string', $this->context->getRequestType(), false, false)
            ->willReturn('predefinedId');
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with('predefinedId', self::identicalTo($associationTargetMetadata))
            ->willReturn(1);

        $this->context->setFilterValues($filterValues);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame(1, $filterValues->get('association')->getValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForAssociationFilterWhenValueIsArray()
    {
        $filters = $this->context->getFilters();
        $associationFilter = new ComparisonFilter('integer');
        $associationFilter->setField('associationField');
        $associationFilter->setArrayAllowed(true);
        $filters->add('association', $associationFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('association', new FilterValue('association', 'predefinedId1,predefinedId2'));

        $metadata = new EntityMetadata();
        $associationMetadata = new AssociationMetadata('associationField');
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName('AssociationTargetClass');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $metadata->addAssociation($associationMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('predefinedId1,predefinedId2', 'string', $this->context->getRequestType(), true, false)
            ->willReturn(['predefinedId1', 'predefinedId2']);
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['predefinedId1', $associationTargetMetadata, 1],
                ['predefinedId2', $associationTargetMetadata, 2]
            ]);

        $this->context->setFilterValues($filterValues);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertSame([1, 2], $filterValues->get('association')->getValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForAssociationFilterWhenValueIsRange()
    {
        $filters = $this->context->getFilters();
        $associationFilter = new ComparisonFilter('integer');
        $associationFilter->setField('associationField');
        $associationFilter->setRangeAllowed(true);
        $filters->add('association', $associationFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('association', new FilterValue('association', 'predefinedId1..predefinedId2'));

        $metadata = new EntityMetadata();
        $associationMetadata = new AssociationMetadata('associationField');
        $associationTargetMetadata = new EntityMetadata();
        $associationTargetMetadata->setClassName('AssociationTargetClass');
        $associationMetadata->setTargetMetadata($associationTargetMetadata);
        $metadata->addAssociation($associationMetadata);

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('predefinedId1..predefinedId2', 'string', $this->context->getRequestType(), false, true)
            ->willReturn(new Range('predefinedId1', 'predefinedId2'));
        $entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdTransformerRegistry->expects(self::once())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($entityIdTransformer);
        $entityIdTransformer->expects(self::exactly(2))
            ->method('reverseTransform')
            ->willReturnMap([
                ['predefinedId1', $associationTargetMetadata, 1],
                ['predefinedId2', $associationTargetMetadata, 2]
            ]);

        $this->context->setFilterValues($filterValues);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        /** @var Range $value */
        $value = $filterValues->get('association')->getValue();
        self::assertInstanceOf(Range::class, $value);
        self::assertSame(1, $value->getFromValue());
        self::assertSame(2, $value->getToValue());

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessForInvalidDataType()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('integer');
        $filters->add('id', $integerFilter);

        $exception = new \UnexpectedValueException('invalid data type');

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', 'invalid'));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('invalid', 'integer', $this->context->getRequestType(), false, false)
            ->willThrowException($exception);

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertEquals('invalid', $filterValues->get('id')->getValue());

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER)
                    ->setInnerException($exception)
                    ->setSource(ErrorSource::createByParameter('id'))
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessForNotSupportedFilter()
    {
        $filters = $this->context->getFilters();
        $integerFilter = new ComparisonFilter('string');
        $filters->add('label', $integerFilter);

        $filterValues = new TestFilterValueAccessor();
        $filterValues->set('id', new FilterValue('id', '1'));
        $filterValues->set('label', new FilterValue('label', 'test'));

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('test', 'string', $this->context->getRequestType(), false, false)
            ->willReturn('test');

        $this->context->setFilterValues($filterValues);
        $this->processor->process($this->context);

        self::assertEquals(
            [
                Error::createValidationError(Constraint::FILTER, 'The filter is not supported.')
                    ->setSource(ErrorSource::createByParameter('id'))
            ],
            $this->context->getErrors()
        );
    }
}
