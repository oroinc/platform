<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\ApiDoc;

use Oro\Bundle\ApiBundle\ApiDoc\PredefinedIdDocumentationProvider;
use Oro\Bundle\ApiBundle\Request\EntityIdResolverRegistry;
use Oro\Bundle\ApiBundle\Request\RequestType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PredefinedIdDocumentationProviderTest extends TestCase
{
    private EntityIdResolverRegistry&MockObject $entityIdResolverRegistry;
    private PredefinedIdDocumentationProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->entityIdResolverRegistry = $this->createMock(EntityIdResolverRegistry::class);

        $this->provider = new PredefinedIdDocumentationProvider($this->entityIdResolverRegistry);
    }

    public function testGetDocumentationWhenNoPredefinedIds(): void
    {
        $requestType = new RequestType(['test']);

        $this->entityIdResolverRegistry->expects(self::once())
            ->method('getDescriptions')
            ->with($requestType)
            ->willReturn([]);

        self::assertNull(
            $this->provider->getDocumentation($requestType)
        );
    }

    public function testGetDocumentationWhenPredefinedIdsExist(): void
    {
        $requestType = new RequestType(['test']);

        $this->entityIdResolverRegistry->expects(self::once())
            ->method('getDescriptions')
            ->with($requestType)
            ->willReturn(['id1 description', 'id2 description']);

        $expectedDocumentation = <<<MARKDOWN
The following predefined identifiers are supported:

- id1 description
- id2 description

All these identifiers can be used in a resource path, filters and request data.
MARKDOWN;

        self::assertEquals(
            $expectedDocumentation,
            $this->provider->getDocumentation($requestType)
        );
    }
}
