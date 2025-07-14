<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\NormalizeEntityClass;
use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class NormalizeEntityClassTest extends BatchUpdateItemProcessorTestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ResourcesProvider&MockObject $resourcesProvider;
    private NormalizeEntityClass $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new NormalizeEntityClass(
            $this->valueNormalizer,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenClassIsNotSet(): void
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getClassName());
        self::assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenClassIsEmpty(): void
    {
        $this->context->setClassName('');
        $this->processor->process($this->context);

        self::assertSame('', $this->context->getClassName());
        self::assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenClassAlreadyNormalized(): void
    {
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->context->setClassName('Test\Entity');
        $this->processor->process($this->context);

        self::assertEquals('Test\Entity', $this->context->getClassName());
    }

    public function testProcessWhenEntityClassIsNotNormalized(): void
    {
        $entityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Entity');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessible')
            ->with('Test\Entity', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setClassName($entityType);
        $this->processor->process($this->context);

        self::assertEquals('Test\Entity', $this->context->getClassName());
    }

    public function testProcessForNotAccessibleEntityType(): void
    {
        $this->expectException(ResourceNotAccessibleException::class);

        $entityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Entity');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessible')
            ->with('Test\Entity', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->context->setClassName($entityType);
        $this->processor->process($this->context);
    }

    public function testProcessForInvalidEntityType(): void
    {
        $entityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($entityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new EntityAliasNotFoundException($entityType));

        $this->context->setClassName($entityType);
        $this->processor->process($this->context);

        self::assertNull($this->context->getClassName());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown entity type: test.')
            ],
            $this->context->getErrors()
        );
    }
}
