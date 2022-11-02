<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest\AddHateoasLinksForEntity;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\MetadataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddHateoasLinksForEntityTest extends MetadataProcessorTestCase
{
    /** @var ResourcesProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $resourcesProvider;

    /** @var AddHateoasLinksForEntity */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);
        $routes = new RestRoutes('item', 'list', 'subresource', 'relationship');
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->processor = new AddHateoasLinksForEntity(
            new RestRoutesRegistry(
                [['routes', 'rest']],
                TestContainerBuilder::create()->add('routes', $routes)->getContainer($this),
                new RequestExpressionMatcher()
            ),
            $urlGenerator,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenNotEntityMetadataInResult()
    {
        $this->processor->process($this->context);
        self::assertFalse($this->context->hasResult());
    }

    public function testProcessWhenSelfLinkAlreadyExistsInEntityMetadata()
    {
        $existingSelfLinkMetadata = new ExternalLinkMetadata('url');
        $entityMetadata = new EntityMetadata('Test\Entity');
        $entityMetadata->addLink('self', $existingSelfLinkMetadata);

        $this->resourcesProvider->expects(self::never())
            ->method('getResourceExcludeActions');

        $this->context->setResult($entityMetadata);
        $this->processor->process($this->context);

        self::assertCount(1, $entityMetadata->getLinks());
        self::assertSame($existingSelfLinkMetadata, $entityMetadata->getLink('self'));
    }

    public function testProcessWhenSelfLinkDoesNotExistInEntityMetadata()
    {
        $entityMetadata = new EntityMetadata('Test\Entity');

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Entity', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiAction::UPDATE_LIST]);

        $this->context->setResult($entityMetadata);
        $this->processor->process($this->context);

        self::assertCount(1, $entityMetadata->getLinks());
        /** @var RouteLinkMetadata $selfLinkMetadata */
        $selfLinkMetadata = $entityMetadata->getLink('self');
        self::assertInstanceOf(RouteLinkMetadata::class, $selfLinkMetadata);
        self::assertEquals(
            [
                'route_name'   => 'item',
                'route_params' => ['entity' => '__type__', 'id' => '__id__']
            ],
            $selfLinkMetadata->toArray()
        );
    }

    public function testProcessWhenSelfLinkDoesNotExistInEntityMetadataAndGetActionExcluded()
    {
        $entityMetadata = new EntityMetadata('Test\Entity');

        $this->resourcesProvider->expects(self::once())
            ->method('getResourceExcludeActions')
            ->with('Test\Entity', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn([ApiAction::UPDATE_LIST, ApiAction::GET]);

        $this->context->setResult($entityMetadata);
        $this->processor->process($this->context);

        self::assertCount(0, $entityMetadata->getLinks());
    }
}
