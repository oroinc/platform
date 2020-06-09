<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Processor\UpdateItem;

use Oro\Bundle\ApiBundle\Batch\Processor\UpdateItem\ValidateEntityTypeSupported;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueNormalizer;
use Oro\Bundle\EntityBundle\Exception\EntityAliasNotFoundException;

class ValidateEntityTypeSupportedTest extends BatchUpdateItemProcessorTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ValueNormalizer */
    private $valueNormalizer;

    /** @var ValidateEntityTypeSupported */
    private $processor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->valueNormalizer = $this->createMock(ValueNormalizer::class);

        $this->processor = new ValidateEntityTypeSupported($this->valueNormalizer);
    }

    public function testProcessWithoutRestrictionsToSupportedEntityClasses()
    {
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWithoutEntityClass()
    {
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassWasNotNormalized()
    {
        $this->context->setClassName('entity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassIsSupported()
    {
        $this->context->setClassName('Test\Entity');
        $this->context->setSupportedEntityClasses(['Test\Entity']);
        $this->processor->process($this->context);

        self::assertFalse($this->context->hasErrors());
    }

    public function testProcessWhenEntityClassIsNotSupported()
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

    public function testProcessWhenEntityClassIsNotSupportedAndEntityTypeResolvingFailed()
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
