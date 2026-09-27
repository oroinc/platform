<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Event;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Source;

final class EmailTemplateSecurityPolicyViolationEventTest extends TestCase
{
    private const array TWIG_CONTEXT = ['confirmationToken' => 'secret-token', 'greeting' => 'Hello'];
    private const string SUBSTITUTED_VALUE = 'REDACTED';

    private SecurityNotAllowedMethodError $violation;
    private Source $source;
    private object $object;
    private EmailTemplateSecurityPolicyViolationEvent $event;

    #[\Override]
    protected function setUp(): void
    {
        $this->violation = new SecurityNotAllowedMethodError('Method is not allowed', 'SomeClass', 'secret');
        $this->source = new Source('', 'sample_template');
        $this->object = new \stdClass();

        $this->event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: $this->violation,
            object: $this->object,
            item: 'secret',
            arguments: ['arg1'],
            type: 'method',
            isDefinedTest: false,
            source: $this->source,
            lineno: 42,
            context: self::TWIG_CONTEXT
        );
    }

    public function testGettersReturnTheConstructorPayload(): void
    {
        self::assertSame($this->violation, $this->event->getViolation());
        self::assertSame($this->object, $this->event->getObject());
        self::assertSame('secret', $this->event->getItem());
        self::assertSame(['arg1'], $this->event->getArguments());
        self::assertSame('method', $this->event->getType());
        self::assertFalse($this->event->isDefinedTest());
        self::assertSame($this->source, $this->event->getSource());
        self::assertSame(42, $this->event->getLineno());
        self::assertSame(self::TWIG_CONTEXT, $this->event->getContext());
    }

    public function testContextIsEmptyWhenNotGiven(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: $this->violation,
            object: $this->object,
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            source: $this->source,
            lineno: 42
        );

        self::assertSame([], $event->getContext());
    }

    public function testValueIsNullAndNotHandledByDefault(): void
    {
        self::assertNull($this->event->getValue());
        self::assertFalse($this->event->isHandled());
    }

    public function testSetValueStoresTheValueAndMarksTheEventHandled(): void
    {
        $this->event->setValue(self::SUBSTITUTED_VALUE);

        self::assertSame(self::SUBSTITUTED_VALUE, $this->event->getValue());
        self::assertTrue($this->event->isHandled());
    }

    public function testSetValueMarksTheEventHandledEvenWhenTheValueIsNull(): void
    {
        $this->event->setValue(null);

        self::assertNull($this->event->getValue());
        self::assertTrue($this->event->isHandled());
    }

    public function testSetHandledMarksTheEventHandledWithoutSubstitutingAValue(): void
    {
        $this->event->setHandled(true);

        self::assertTrue($this->event->isHandled());
        self::assertNull($this->event->getValue());
    }

    public function testSetHandledClearsTheHandledFlagAndKeepsTheSubstitutedValue(): void
    {
        $this->event->setValue(self::SUBSTITUTED_VALUE);

        $this->event->setHandled(false);

        self::assertFalse($this->event->isHandled());
        self::assertSame(self::SUBSTITUTED_VALUE, $this->event->getValue());
    }
}
