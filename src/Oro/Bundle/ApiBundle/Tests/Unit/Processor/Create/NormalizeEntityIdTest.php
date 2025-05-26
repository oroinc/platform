<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Model\NotResolvedIdentifier;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Create\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use PHPUnit\Framework\MockObject\MockObject;

class NormalizeEntityIdTest extends FormProcessorTestCase
{
    private EntityIdTransformerInterface&MockObject $entityIdTransformer;
    private NormalizeEntityId $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->entityIdTransformer = $this->createMock(EntityIdTransformerInterface::class);
        $entityIdTransformerRegistry = $this->createMock(EntityIdTransformerRegistry::class);
        $entityIdTransformerRegistry->expects(self::any())
            ->method('getEntityIdTransformer')
            ->with($this->context->getRequestType())
            ->willReturn($this->entityIdTransformer);

        $this->processor = new NormalizeEntityId($entityIdTransformerRegistry);
    }

    #[\Override]
    protected function createContext(): FormContext
    {
        return new CreateContext($this->configProvider, $this->metadataProvider);
    }

    public function testProcessWhenIdAlreadyNormalized(): void
    {
        $this->context->setClassName('Test\Class');
        $this->context->setId(123);

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcessWhenEnumIdAlreadyNormalized(): void
    {
        $this->context->setClassName('Extend\Entity\EV_Test_Enum');
        $this->context->setId('test_enum.option1');

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityHasIdentifierGenerator(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(true);

        $this->context->setClassName('Test\Class');
        $this->context->setId('123');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcess(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(false);

        $this->context->setClassName('Test\Class');
        $this->context->setId('123');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getId(), self::identicalTo($metadata))
            ->willReturn(123);

        $this->processor->process($this->context);

        self::assertSame(123, $this->context->getId());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessForEnum(): void
    {
        $metadata = new EntityMetadata('Extend\Entity\EV_Test_Enum');
        $metadata->setHasIdentifierGenerator(false);

        $this->context->setClassName('Extend\Entity\EV_Test_Enum');
        $this->context->setId('option1');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getId(), self::identicalTo($metadata))
            ->willReturn('test_enum.option1');

        $this->processor->process($this->context);

        self::assertSame('test_enum.option1', $this->context->getId());
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessForInvalidId(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(false);

        $this->context->setClassName('Test\Class');
        $this->context->setId('123');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getId(), self::identicalTo($metadata))
            ->willThrowException(new \Exception('some error'));

        $this->processor->process($this->context);

        self::assertSame('123', $this->context->getId());
        self::assertEquals(
            [
                Error::createValidationError('entity identifier constraint')
                    ->setInnerException(new \Exception('some error'))
            ],
            $this->context->getErrors()
        );
        self::assertSame([], $this->context->getNotResolvedIdentifiers());
    }

    public function testProcessForNotResolvedId(): void
    {
        $metadata = new EntityMetadata('Test\Entity');
        $metadata->setHasIdentifierGenerator(false);

        $this->context->setClassName('Test\Class');
        $this->context->setId('test');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::once())
            ->method('reverseTransform')
            ->with($this->context->getId(), self::identicalTo($metadata))
            ->willReturn(null);

        $this->processor->process($this->context);

        self::assertNull($this->context->getId());
        self::assertEquals(
            ['id' => new NotResolvedIdentifier('test', 'Test\Class')],
            $this->context->getNotResolvedIdentifiers()
        );
    }
}
