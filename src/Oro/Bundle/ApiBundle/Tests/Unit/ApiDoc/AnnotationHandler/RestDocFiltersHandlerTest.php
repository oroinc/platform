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
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestDocFiltersHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const VIEW = 'test_view';

    /** @var RequestType */
    private $requestType;

    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var ApiDocDataTypeConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $dataTypeConverter;

    /** @var FiltersSorterRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $sorterRegistry;

    /** @var RestDocFiltersHandler */
    private $filtersHandler;

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

    public function testHandleWithoutFilters()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

        self::assertSame([], $annotation->getFilters());
    }

    public function testHandleWithFiltersInAnnotationButNoFiltersInFilterCollection()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $annotation->addFilter('firstName', ['type' => 'string']);
        $annotation->addFilter('lastName', ['type' => 'string']);
        $annotation->addFilter('id', ['type' => 'int']);

        $this->filtersHandler->handle($annotation, $filters, $metadata);

        self::assertSame(
            [
                'firstName' => ['type' => 'string'],
                'id'        => ['type' => 'int'],
                'lastName'  => ['type' => 'string']
            ],
            $annotation->getFilters()
        );
    }

    public function testHandleForNonStandaloneFilter()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filters->add('filter1', $this->createMock(FilterInterface::class));

        $this->filtersHandler->handle($annotation, $filters, $metadata);

        self::assertSame([], $annotation->getFilters());
    }

    public function testHandleForStandaloneFilter()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new StandaloneFilter('int', 'A filter');
        $filters->add('filter1', $filter1);

        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForStandaloneFilterWithDefaultValue()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new StandaloneFilterWithDefaultValue('int', 'A filter', 123);
        $filters->add('filter1', $filter1);

        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilter()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filter1->setSupportedOperators([FilterOperator::EQ, FilterOperator::NEQ]);
        $filters->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterWithOnlyEqualsOperator()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filter1->setSupportedOperators([FilterOperator::EQ]);
        $filters->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterWithArrayAllowed()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filter1->setArrayAllowed(true);
        $filters->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterWithRangeAllowed()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filter1->setRangeAllowed(true);
        $filters->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterWithArrayAndRangeAllowed()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filter1->setArrayAllowed(true);
        $filter1->setRangeAllowed(true);
        $filters->add('filter1', $filter1);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterForAssociation()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filters->add('filter1', $filter1);

        $association = $metadata->addAssociation(new AssociationMetadata('field1'));
        $association->setDataType('int');
        $association->addAcceptableTargetClassName('Test\TargetEntity');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\TargetEntity', DataType::ENTITY_TYPE, $this->requestType)
            ->willReturn('target_entity');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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

    public function testHandleForComparisonFilterForAssociationThatShouldBeRepresentedAsField()
    {
        $annotation = new ApiDoc([]);
        $filters = new FilterCollection();
        $metadata = new EntityMetadata('Test\Entity');

        $filter1 = new ComparisonFilter('int', 'A filter');
        $filter1->setField('field1');
        $filters->add('filter1', $filter1);

        $association = $metadata->addAssociation(new AssociationMetadata('field1'));
        $association->setDataType('object');
        $association->addAcceptableTargetClassName('Test\TargetEntity');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
            ->willReturn('\d+');
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->filtersHandler->handle($annotation, $filters, $metadata);

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
