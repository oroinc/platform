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
use Symfony\Component\Routing\Route;

class RestDocIdentifierHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const VIEW = 'test_view';

    /** @var RequestType */
    private $requestType;

    /** @var ValueNormalizer|\PHPUnit\Framework\MockObject\MockObject */
    private $valueNormalizer;

    /** @var ApiDocDataTypeConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $dataTypeConverter;

    /** @var RestDocIdentifierHandler */
    private $identifierHandler;

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

    public function testHandleForSingleIdentifier()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');
        $metadata = new EntityMetadata('Test\Entity');
        $description = null;

        $metadata->setIdentifierFieldNames(['id']);
        $metadata->addField(new FieldMetadata('id'))->setDataType('int');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('int', self::VIEW)
            ->willReturn('integer');
        $this->valueNormalizer->expects(self::once())
            ->method('getRequirement')
            ->with('int', $this->requestType)
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

    public function testHandleForCompositeIdentifier()
    {
        $annotation = new ApiDoc([]);
        $route = new Route('/test_route');
        $metadata = new EntityMetadata('Test\Entity');
        $description = 'ID field description';

        $metadata->setIdentifierFieldNames(['id1', 'id2']);
        $metadata->addField(new FieldMetadata('id1'))->setDataType('int');
        $metadata->addField(new FieldMetadata('id2'))->setDataType('string');

        $this->dataTypeConverter->expects(self::once())
            ->method('convertDataType')
            ->with('string', self::VIEW)
            ->willReturn('string');
        $this->valueNormalizer->expects(self::exactly(2))
            ->method('getRequirement')
            ->willReturnMap([
                ['int', $this->requestType, false, false, '\d+'],
                ['string', $this->requestType, false, false, ValueNormalizer::DEFAULT_REQUIREMENT]
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

    public function testHandleWhenNoIdentifier()
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
