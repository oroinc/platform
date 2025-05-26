<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContextParentMetadataAccessorTest extends TestCase
{
    private SubresourceContext&MockObject $context;
    private ContextParentMetadataAccessor $metadataAccessor;

    #[\Override]
    protected function setUp(): void
    {
        $this->context = $this->createMock(SubresourceContext::class);

        $this->metadataAccessor = new ContextParentMetadataAccessor($this->context);
    }

    public function testGetMetadataForContextParentClass(): void
    {
        $className = User::class;
        $metadata = new EntityMetadata('Test\Entity');

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getParentMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForContextParentClassForCaseWhenParentApiResourceIsBasedOnManageableEntity(): void
    {
        $className = User::class;
        $metadata = new EntityMetadata('Test\Entity');

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(UserProfile::class);
        $this->context->expects(self::once())
            ->method('getParentMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForNotContextParentClass(): void
    {
        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(User::class);
        $this->context->expects(self::never())
            ->method('getParentMetadata');

        self::assertNull($this->metadataAccessor->getMetadata(Product::class));
    }
}
