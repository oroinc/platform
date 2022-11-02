<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\NormalizeParentEntityId;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;

class NormalizeParentEntityIdTest extends GetSubresourceProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var NormalizeParentEntityId */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeParentEntityId($entityIdTransformerRegistry);
    }

    public function testProcessWhenParentIdAlreadyNormalized()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setParentId(123);

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testConvertStringParentIdToConcreteDataType()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setParentId('123');
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getParentId(), self::identicalTo($this->context->getParentMetadata()))
            ->willReturn(123);

        $this->processor->process($this->context);

        self::assertSame(123, $this->context->getParentId());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessForInvalidParentId()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setParentId('123');
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getParentId(), self::identicalTo($this->context->getParentMetadata()))
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        self::assertSame('123', $this->context->getParentId());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('some error'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessForNotResolvedParentId()
    {
        $this->context->setParentClassName('Test\Class');
        $this->context->setParentId('test');
        $this->context->setParentMetadata(new EntityMetadata('Test\Entity'));

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getParentId(), self::identicalTo($this->context->getParentMetadata()))
            ->willReturn(null);

        $this->processor->process($this->context);

        self::assertNull($this->context->getParentId());
        self::assertEquals(
            ['parentId' => new NotResolvedIdentifier('test', 'Test\Class')],
            $this->context->getNotResolvedIdentifiers()
        );
    }
}
