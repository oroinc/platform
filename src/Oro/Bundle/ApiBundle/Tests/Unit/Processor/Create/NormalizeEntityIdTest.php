<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Create;

use Oro\Bundle\ApiBundle\Metadata\EntityMetadata;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Create\CreateContext;
use Oro\Bundle\ApiBundle\Processor\Create\NormalizeEntityId;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerInterface;
use Oro\Bundle\ApiBundle\Request\EntityIdTransformerRegistry;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;

class NormalizeEntityIdTest extends FormProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|EntityIdTransformerInterface */
    private $entityIdTransformer;

    /** @var NormalizeEntityId */
    private $processor;

    protected function setUp()
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

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        return new CreateContext($this->configProvider, $this->metadataProvider);
    }

    public function testProcessWhenIdAlreadyNormalized()
    {
        $this->context->setClassName('Test\Class');
        $this->context->setId(123);

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcessWhenEntityHasIdentifierGenerator()
    {
        $metadata = new EntityMetadata();
        $metadata->setHasIdentifierGenerator(true);

        $this->context->setClassName('Test\Class');
        $this->context->setId('123');
        $this->context->setMetadata($metadata);

        $this->entityIdTransformer->expects(self::never())
            ->method('reverseTransform');

        $this->processor->process($this->context);
    }

    public function testProcess()
    {
        $metadata = new EntityMetadata();
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
    }

    public function testProcessForInvalidId()
    {
        $metadata = new EntityMetadata();
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
    }
}
