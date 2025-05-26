<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc\AnnotationHandler;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ApiBundle\ApiDoc\AnnotationHandler\RestDocIdentifierHandler;
use Oro\Bundle\ApiBundle\ApiDoc\ApiDocDataTypeConverter;
use Oro\Bundle\ApiBundle\ApiDoc\RestDocViewDetector;
use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\FieldMetadata;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Route;

class RestDocIdentifierHandlerTest extends TestCase
{
    private const string VIEW = 'test_view';

    private RequestType $requestType;
    private ValueNormalizer&MockObject $valueNormalizer;
    private ApiDocDataTypeConverter&MockObject $dataTypeConverter;
    private RestDocIdentifierHandler $identifierHandler;

    #[\Override]
    protected function setUp(): void
    {
        $this->requestType = new RequestType([RequestType::REST]);

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->dataTypeConverter = $this->createMock(ApiDocDataTypeConverter::class);

        $docViewDetector = $this->createMock(RestDocViewDetector::class);
        $docViewDetector->expects(self::any())
            ->method('getRequestType')
            ->willReturn($this->requestType);
        $docViewDetector->expects(self::any())
            ->method('getView')
            ->willReturn(self::VIEW);

        $this->identifierHandler = new RestDocIdentifierHandler(
            $docViewDetector,
            $this->valueNormalizer,
            $this->dataTypeConverter
        );
    }

    public function testHandleForSingleIdentifier(): void
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');
        $metadata = new EntityMetadata('Test\Entity');
        $description = null;

        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('integer');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('integer', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('integer', $this->requestType, false, false, [])
            ->willReturn('\d+');

        $this->identifierHandler->handle($annotation, $route, $metadata, $description);

        self::assertSame(
            [
                'id' => [
                    'dataType'    => 'integer',
                    'requirement' => '\d+',
                    'description' => $description
                ]
            ],
            $annotation->getRequirements()
        );
    }

    public function testHandleForCompositeIdentifier(): void
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');
        $metadata = new EntityMetadata('Test\Entity');
        $description = 'ID field description';

        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('integer');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('string', self::VIEW)
            ->willReturn('string');
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('getRequirement')
            ->willReturnMap([
                ['integer', $this->requestType, false, false, [], '\d+'],
                ['string', $this->requestType, false, false, [], ValueNormalizer::DEFAULT_REQUIREMENT]
            ]);

        $this->identifierHandler->handle($annotation, $route, $metadata, $description);

        self::assertSame(
            [
                'id' => [
                    'dataType'    => 'string',
                    'requirement' => 'id1=\d+,id2=[^\.]+',
                    'description' => $description
                ]
            ],
            $annotation->getRequirements()
        );
    }

    public function testHandleWhenNoIdentifier(): void
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');
        $metadata = new EntityMetadata('Test\Entity');
        $description = null;

        $metadata->setIdentifierFieldNames([]);

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('string', self::VIEW)
            ->willReturn('string');

        $this->identifierHandler->handle($annotation, $route, $metadata, $description);

        self::assertSame(
            [
                'id' => [
                    'dataType'    => 'string',
                    'requirement' => '',
                    'description' => $description
                ]
            ],
            $annotation->getRequirements()
        );
    }
}
