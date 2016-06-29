<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\Rest;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Oro\Bundle\ApiBundle\Processor\Create\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class SetLocationHeaderTest extends FormProcessorTestCase
{
    const RESPONSE_LOCATION_HEADER_NAME = 'Location';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityIdTransformer;

    /** @var SetLocationHeader */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->valueNormalizer = $this->getMockBuilder('Oro\Bundle\ApiBundle\Request\ValueNormalizer')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityIdTransformer = $this->getMock('Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface');

        $this->processor = new SetLocationHeader(
            $this->router,
            $this->valueNormalizer,
            $this->entityIdTransformer
        );
    }

    public function testProcessOnExistingHeader()
    {
        $existingLocation = 'existing location';

        $this->context->getResponseHeaders()->set(self::RESPONSE_LOCATION_HEADER_NAME, $existingLocation);
        $this->processor->process($this->context);

        $this->assertEquals(
            $existingLocation,
            $this->context->getResponseHeaders()->get(self::RESPONSE_LOCATION_HEADER_NAME)
        );
    }

    public function testProcess()
    {
        $location = 'test location';
        $entityClass = 'Test\Entity';
        $entityType = 'test_entity';
        $entityId = 123;
        $transformedEntityId = 'transformed_123';

        $this->valueNormalizer->expects($this->once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $this->context->getRequestType())
            ->willReturn($entityType);
        $this->entityIdTransformer->expects($this->once())
            ->method('transform')
            ->with($entityId)
            ->willReturn($transformedEntityId);
        $this->router->expects($this->once())
            ->method('generate')
            ->with(
                'oro_rest_api_get',
                ['entity' => $entityType, 'id' => $transformedEntityId],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($location);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->processor->process($this->context);

        $this->assertEquals(
            $location,
            $this->context->getResponseHeaders()->get(self::RESPONSE_LOCATION_HEADER_NAME)
        );
    }
}
