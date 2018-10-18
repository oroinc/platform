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
use Symfony\Component\DependencyInjection\ContainerInterface;

class EntityIdTransformerRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $transformer1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $transformer2;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdResolverRegistry */
    private $entityIdResolverRegistry;

    /** @var EntityIdTransformerRegistry */
    private $registry;

    protected function setUp()
    {
        $this->transformer1 = $this->createMock(EntityIdTransformerInterface::class);
        $this->transformer2 = $this->createMock(EntityIdTransformerInterface::class);
        $this->entityIdResolverRegistry = $this->createMock(EntityIdResolverRegistry::class);
        $this->container = TestContainerBuilder::create()
            ->add('transformer1', $this->transformer1)
            ->add('transformer2', $this->transformer2)
            ->getContainer($this);

        $this->registry = new EntityIdTransformerRegistry(
            [
                ['transformer1', 'rest&!json_api'],
                ['transformer2', 'json_api']
            ],
            $this->container,
            new RequestExpressionMatcher(),
            $this->entityIdResolverRegistry
        );
    }

    public function testGetEntityIdTransformerForKnownRequestType()
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

    public function testGetEntityIdTransformerForUnknownRequestType()
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
