<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\Shared;

use Oro\Bundle\ApiBundle\Exception\ResourceNotAccessibleException;
use Oro\Bundle\ApiBundle\Model\Error;
use Oro\Bundle\ApiBundle\Processor\Subresource\Shared\NormalizeParentEntityClass;
use Oro\Bundle\ApiBundle\Provider\ResourcesProvider;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Subresource\GetSubresourceProcessorTestCase;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class NormalizeParentEntityClassTest extends GetSubresourceProcessorTestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ResourcesProvider&MockObject $resourcesProvider;
    private NormalizeParentEntityClass $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);
        $this->resourcesProvider = $this->createMock(ResourcesProvider::class);

        $this->processor = new NormalizeParentEntityClass(
            $this->valueNormalizer,
            $this->resourcesProvider
        );
    }

    public function testProcessWhenParentClassNameIsNotSet(): void
    {
        $this->processor->process($this->context);

        self::assertNull($this->context->getParentClassName());
        self::assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The parent entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenParentClassNameIsEmpty(): void
    {
        $this->context->setParentClassName('');
        $this->processor->process($this->context);

        self::assertSame('', $this->context->getParentClassName());
        self::assertEquals(
            [
                Error::createValidationError(
                    'entity type constraint',
                    'The parent entity class must be set in the context.'
                )
            ],
            $this->context->getErrors()
        );
    }

    public function testProcessWhenParentClassAlreadyNormalized(): void
    {
        $this->valueNormalizer->expects(self::never())
            ->method('normalizeValue');

        $this->context->setParentClassName('Test\Class');
        $this->processor->process($this->context);
    }

    public function testConvertParentEntityTypeToEntityClass(): void
    {
        $parentEntityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($parentEntityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(true);

        $this->context->setParentClassName($parentEntityType);
        $this->processor->process($this->context);

        self::assertSame('Test\Class', $this->context->getParentClassName());
    }

    public function testProcessForNotAccessibleParentEntityType(): void
    {
        $this->expectException(ResourceNotAccessibleException::class);

        $parentEntityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($parentEntityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willReturn('Test\Class');
        $this->resourcesProvider->expects(self::once())
            ->method('isResourceAccessibleAsAssociation')
            ->with('Test\Class', $this->context->getVersion(), $this->context->getRequestType())
            ->willReturn(false);

        $this->context->setParentClassName($parentEntityType);
        $this->processor->process($this->context);
    }

    public function testProcessForInvalidParentEntityType(): void
    {
        $parentEntityType = 'test';

        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with($parentEntityType, DataType::ENTITY_CLASS, $this->context->getRequestType())
            ->willThrowException(new EntityAliasNotFoundException($parentEntityType));

        $this->context->setParentClassName($parentEntityType);
        $this->processor->process($this->context);

        self::assertNull($this->context->getParentClassName());
        self::assertEquals(
            [
                Error::createValidationError('entity type constraint', 'Unknown parent entity type: test.', 404)
            ],
            $this->context->getErrors()
        );
    }
}
