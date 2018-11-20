<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\Rest;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Metadata\ExternalLinkMetadata;
use Oro\Bundle\ApiBundle\Metadata\RouteLinkMetadata;
use Oro\Bundle\ApiBundle\Processor\GetMetadata\Rest\AddHateoasLinksForEntity;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutes;
use Oro\Bundle\ApiBundle\Request\Rest\RestRoutesRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\GetMetadata\MetadataProcessorTestCase;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AddHateoasLinksForEntityTest extends MetadataProcessorTestCase
{
    /** @var AddHateoasLinksForEntity */
    private $processor;

    protected function setUp()
    {
        parent::setUp();
        $routes = new RestRoutes('item', 'list', 'subresource', 'relationship');
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->processor = new AddHateoasLinksForEntity(
            new RestRoutesRegistry([[$routes, 'rest']], new RequestExpressionMatcher()),
            $urlGenerator
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
        $entityMetadata = new EntityMetadata();
        $entityMetadata->addLink('self', $existingSelfLinkMetadata);

        $this->context->setResult($entityMetadata);
        $this->processor->process($this->context);

        self::assertCount(1, $entityMetadata->getLinks());
        self::assertSame($existingSelfLinkMetadata, $entityMetadata->getLink('self'));
    }

    public function testProcessWhenSelfLinkDoesNotExistInEntityMetadata()
    {
        $entityMetadata = new EntityMetadata();

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
}
