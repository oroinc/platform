<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\ValidateEntityTypeSupported;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;
use PHPUnit\Framework\MockObject\MockObject;

class ValidateEntityTypeSupportedTest extends BatchUpdateItemProcessorTestCase
{
    private ValueNormalizer&MockObject $valueNormalizer;
    private ValidateEntityTypeSupported $processor;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new ValidateEntityTypeSupported($this->valueNormalizer);
    }

    public function testProcessWithoutRestrictionsToSupportedEntityClasses(): void
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutEntityClass(): void
    {
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassWasNotNormalized(): void
    {
        $this->context->setClassName('entity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassIsSupported(): void
    {
        $this->context->setClassName('Test\Entity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassIsNotSupported(): void
    {
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\AnotherEntity', DataType::ENTITY_TYPE, $this->context->getRequestType())
            ->willReturn('entity');

        $this->context->setClassName('Test\AnotherEntity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        $errors = $this->context->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals(400, $errors[0]->getStatusCode());
        self::assertEquals(Constraint::ENTITY_TYPE, $errors[0]->getTitle());
        self::assertEquals(
            'The entity type "entity" is not supported by this batch operation.',
            $errors[0]->getDetail()
        );
    }

    public function testProcessWhenEntityClassIsNotSupportedAndEntityTypeResolvingFailed(): void
    {
        $this->valueNormalizer->expects(self::once())
            ->method('normalizeValue')
            ->with('Test\AnotherEntity', DataType::ENTITY_TYPE, $this->context->getRequestType())
            ->willThrowException(new EntityAliasNotFoundException('Test\AnotherEntity'));

        $this->context->setClassName('Test\AnotherEntity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        $errors = $this->context->getErrors();
        self::assertCount(1, $errors);
        self::assertEquals(400, $errors[0]->getStatusCode());
        self::assertEquals(Constraint::ENTITY_TYPE, $errors[0]->getTitle());
        self::assertEquals(
            'The entity type "Test\AnotherEntity" is not supported by this batch operation.',
            $errors[0]->getDetail()
        );
    }
}
