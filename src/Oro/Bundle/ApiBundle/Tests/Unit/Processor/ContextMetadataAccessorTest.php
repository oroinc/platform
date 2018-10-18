<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Processor\Context;
use Oro\Bundle\ApiBundle\Processor\ContextMetadataAccessor;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\Product;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\User;
use Oro\Bundle\ApiBundle\Tests\Unit\Fixtures\Entity\UserProfile;

class ContextMetadataAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Context */
    private $context;

    /** @var ContextMetadataAccessor */
    private $metadataAccessor;

    protected function setUp()
    {
        $this->context = $this->createMock(Context::class);

        $this->metadataAccessor = new ContextMetadataAccessor($this->context);
    }

    public function testGetMetadataForContextClass()
    {
        $className = User::class;
        $metadata = new EntityMetadata();

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn($className);
        $this->context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForContextClassForCaseWhenApiResourceIsBasedOnManageableEntity()
    {
        $className = User::class;
        $metadata = new EntityMetadata();

        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn(UserProfile::class);
        $this->context->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        self::assertSame($metadata, $this->metadataAccessor->getMetadata($className));
    }

    public function testGetMetadataForNotContextClass()
    {
        $this->context->expects(self::once())
            ->method('getClassName')
            ->willReturn(User::class);
        $this->context->expects(self::never())
            ->method('getMetadata');

        self::assertNull($this->metadataAccessor->getMetadata(Product::class));
    }
}
