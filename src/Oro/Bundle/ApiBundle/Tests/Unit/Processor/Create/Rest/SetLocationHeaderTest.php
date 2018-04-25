<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class SetLocationHeaderTest extends FormProcessorTestCase
{
    private const ITEM_ROUTE_NAME = 'item_route';

    /** @var \PHPUnit_Framework_MockObject_MockObject|RouterInterface */
    private $router;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit_Framework_MockObject_MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var SetLocationHeader */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->router = $this->createMock(RouterInterface::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $this->processor = new SetLocationHeader(
            self::ITEM_ROUTE_NAME,
            $this->router,
            $this->valueNormalizer,
            $this->entityIdTransformer
        );
    }

    public function testProcessWhenHeaderAlreadExist()
    {
        $existingLocation = 'existing location';

        $this->context->getResponseHeaders()->set(SetLocationHeader::RESPONSE_HEADER_NAME, $existingLocation);
        $this->context->setId(123);
        $this->processor->process($this->context);

        self::assertEquals(
            $existingLocation,
            $this->context->getResponseHeaders()->get(SetLocationHeader::RESPONSE_HEADER_NAME)
        );
    }

    public function testProcessWhenNoId()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->getResponseHeaders()->has(SetLocationHeader::RESPONSE_HEADER_NAME));
    }

    public function testProcess()
    {
        $location = 'test location';
        $entityClass = 'Test\Entity';
        $entityType = 'test_entity';
        $entityId = 123;
        $transformedEntityId = 'transformed_123';
        $metadata = new EntityMetadata();

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityClass, DataType::ENTITY_TYPE, $this->context->getRequestType())
            ->willReturn($entityType);
        $this->entityIdTransformer->expects(self::once())
            ->method('transform')
            ->with($entityId, self::identicalTo($metadata))
            ->willReturn($transformedEntityId);
        $this->router->expects(self::once())
            ->method('generate')
            ->with(
                self::ITEM_ROUTE_NAME,
                ['entity' => $entityType, 'id' => $transformedEntityId],
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturn($location);

        $this->context->setClassName($entityClass);
        $this->context->setId($entityId);
        $this->context->setMetadata($metadata);
        $this->processor->process($this->context);

        self::assertEquals(
            $location,
            $this->context->getResponseHeaders()->get(SetLocationHeader::RESPONSE_HEADER_NAME)
        );
    }
}
