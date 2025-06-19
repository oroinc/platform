<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocFiltersHandler;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\ApiDoc\Sorter\FiltersSorterRegistry;
use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\FilterCollection;
use Oro\Bundle\ApiBundle\Filter\FilterInterface;
use Oro\Bundle\ApiBundle\Filter\FilterOperator;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilter;
use Oro\Bundle\ApiBundle\Filter\StandaloneFilterWithDefaultValue;
use Oro\Bundle\ApiBundle\Filter\StringComparisonFilter;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocFiltersHandlerTest extends TestCase
{
    private const string VIEW = 'test_view';

    private RequestType $requestType;
    private ValueNormalizer&MockObject $valueNormalizer;
    private ApiDocDataTypeConverter&MockObject $dataTypeConverter;
    private FiltersSorterRegistry&MockObject $sorterRegistry;
    private RestDocFiltersHandler $filtersHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestType = new RequestType([RequestType::REST]);

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->dataTypeConverter = $this->createMock(ApiDocDataTypeConverter::class);
        $this->sorterRegistry = $this->createMock(FiltersSorterRegistry::class);

        $docViewDetector = $this->createMock(RestDocViewDetector::class);
        $docViewDetector->expects(self::any())
            ->method('getRequestType')
            ->willReturn($this->requestType);
        $docViewDetector->expects(self::any())
            ->method('getView')
            ->willReturn(self::VIEW);

        $this->filtersHandler = new RestDocFiltersHandler(
            $docViewDetector,
            $this->valueNormalizer,
            $this->dataTypeConverter,
            $this->sorterRegistry
        );
    }

    public function testHandleWithoutFilters(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame([], $annotation->getFilters());
    }

    public function testHandleWithFiltersInAnnotationButNoFiltersInFilterCollection(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $annotation->addFilter('firstName', ['type' => 'string']);
        $annotation->addFilter('lastName', ['type' => 'string']);
        $annotation->addFilter('id', ['type' => 'integer']);

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'firstName' => ['type' => 'string'],
                'id'        => ['type' => 'integer'],
                'lastName'  => ['type' => 'string']
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForNonStandaloneFilter(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filterCollection->add('filter1', $this->createMock(FilterInterface::class));

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame([], $annotation->getFilters());
    }

    public function testHandleForStandaloneFilter(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new StandaloneFilter('integer', 'A filter');
        $filterCollection->add('filter1', $filter1);

        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForStandaloneFilterWithDefaultValue(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new StandaloneFilterWithDefaultValue('integer', 'A filter', 123);
        $filterCollection->add('filter1', $filter1);

        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'default'     => '123'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilter(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filter1->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer',
                    'operators'   => 'eq,neq'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterWithOnlyEqualsOperator(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filter1->setSupportedOperators([FilterOperator::EQ]);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterWithArrayAllowed(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filter1->setArrayAllowed(true);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, true, false, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer or array'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterWithRangeAllowed(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filter1->setRangeAllowed(true);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, true, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer or range'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterWithArrayAndRangeAllowed(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filter1->setArrayAllowed(true);
        $filter1->setRangeAllowed(true);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, true, true, [])
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer or array or range'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForStringComparisonFilterWithEmptyValueAllowed(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new StringComparisonFilter('string', 'A filter');
        $filter1->setField('field1');
        $filter1->setAllowEmpty(true);
        $filterCollection->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('string', self::VIEW)
            ->willReturn('string');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('string', $this->requestType, false, false, ['allow_empty' => true])
            ->willReturn('.+');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '.+',
                    'type'        => 'string'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterForAssociation(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filterCollection->add('filter1', $filter1);

        $association = $metadata->addAssociation(new AssociationMetadata('field1'));
        $association->setDataType('integer');
        $association->addAcceptableTargetClassName('Test\TargetEntity');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetEntity', DataType::ENTITY_TYPE, $this->requestType, false, false, [])
            ->willReturn('target_entity');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer',
                    'relation'    => 'target_entity'
                ]
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForComparisonFilterForAssociationThatShouldBeRepresentedAsField(): void
    {
        $annotation = new ApiDoc([]);
        $filterCollection = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('integer', 'A filter');
        $filter1->setField('field1');
        $filterCollection->add('filter1', $filter1);

        $association = $metadata->addAssociation(new AssociationMetadata('field1'));
        $association->setDataType('object');
        $association->addAcceptableTargetClassName('Test\TargetEntity');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->filtersHandler->handle($annotation, $filterCollection, $metadata);

        self::assertSame(
            [
                'filter1' => [
                    'description' => 'A filter',
                    'requirement' => '\d+',
                    'type'        => 'integer'
                ]
            ],
            $annotation->getFilters()
        );
    }
}
