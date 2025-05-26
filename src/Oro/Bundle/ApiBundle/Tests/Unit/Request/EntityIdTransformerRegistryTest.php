<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Request;

use Oro\Bundle\ApiBundle\Request\CombinedEntityIdTransformer;
use Oro\Bundle\ApiBundle\Request\EntityIdResolverRegistry;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Request\NullEntityIdTransformer;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EntityIdTransformerRegistryTest extends TestCase
{
    private EntityIdTransformerInterface&MockObject $transformer1;
    private EntityIdTransformerInterface&MockObject $transformer2;
    private EntityIdResolverRegistry&MockObject $entityIdResolverRegistry;
    private EntityIdTransformerRegistry $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer1 = $this->createMock(EntityIdTransformerInterface::class);
        $this->transformer2 = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdResolverRegistry = $this->createMock(EntityIdResolverRegistry::class);

        $container = TestContainerBuilder::create()
            ->add('transformer1', $this->transformer1)
            ->add('transformer2', $this->transformer2)
            ->getContainer($this);

        $this->registry = new EntityIdTransformerRegistry(
            [
                ['transformer1', 'rest&!json_api'],
                ['transformer2', 'json_api']
            ],
            $container,
            new RequestExpressionMatcher(),
            $this->entityIdResolverRegistry
        );
    }

    public function testGetEntityIdTransformerForKnownRequestType(): void
    {
        $requestType = new RequestType(['rest', 'json_api']);
        self::assertEquals(
            new CombinedEntityIdTransformer(
                $this->transformer2,
                $this->entityIdResolverRegistry,
                $requestType
            ),
            $this->registry->getEntityIdTransformer($requestType)
        );
    }

    public function testGetEntityIdTransformerForUnknownRequestType(): void
    {
        $requestType = new RequestType(['another']);
        self::assertEquals(
            new CombinedEntityIdTransformer(
                NullEntityIdTransformer::getInstance(),
                $this->entityIdResolverRegistry,
                $requestType
            ),
            $this->registry->getEntityIdTransformer($requestType)
        );
    }
}
