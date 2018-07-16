<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Subresource\ContextParentMetadataAccessor;
use Oro\Bundle\ApiBundle\Processor\Subresource\SubresourceContext;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;

class ContextParentMetadataAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|SubresourceContext */
    private $context;

    /** @var ContextParentMetadataAccessor */
    private $metadataAccessor;

    protected function setUp()
    {
        $this->context = $this->createMock(SubresourceContext::class);

        $this->metadataAccessor = new ContextParentMetadataAccessor($this->context);
    }

    public function testGetMetadataForContextParentClass()
    {
        $className = User::class;
        $metadata = new EntityMetadata();

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getParentMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForContextParentClassForCaseWhenParentApiResourceIsBasedOnManageableEntity()
    {
        $className = User::class;
        $metadata = new EntityMetadata();

        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(UserProfile::class);
        $this->context->expects(self::once())
            ->method('getParentMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForNotContextParentClass()
    {
        $this->context->expects(self::once())
            ->method('getParentClassName')
            ->willReturn(User::class);
        $this->context->expects(self::never())
            ->method('getParentMetadata');

        self::assertNull($this->metadataAccessor->getMetadata(Product::class));
    }
}
