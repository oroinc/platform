<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Create\Rest\SetLocationHeader;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SetLocationHeaderTest extends FormProcessorTestCase
{
    private const ITEM_ROUTE_NAME = 'item_route';

    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    private $urlGenerator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var SetLocationHeader */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);

        $routes = $this->createMock(RestRoutes::class);
        $routes->expects(self::any())
            ->method('getItemRouteName')
            ->willReturn(self::ITEM_ROUTE_NAME);

        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new SetLocationHeader(
            new RestRoutesRegistry([[$routes, 'rest']], new RequestExpressionMatcher()),
            $this->urlGenerator,
            $this->valueNormalizer,
            $entityIdTransformerRegistry
        );
    }

    public function testProcessWhenHeaderAlreadyExist()
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
        $this->urlGenerator->expects(self::once())
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
