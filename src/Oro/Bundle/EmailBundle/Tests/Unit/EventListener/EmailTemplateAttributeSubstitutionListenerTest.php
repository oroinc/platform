<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\EventListener\EmailTemplateAttributeSubstitutionListener;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Source;

final class EmailTemplateAttributeSubstitutionListenerTest extends TestCase
{
    private const string VALUE = 'sample-confirmation-token';

    private EmailTemplateAttributeSubstitutionListener $listener;

    private \ArrayObject $record;

    #[\Override]
    protected function setUp(): void
    {
        $this->listener = new EmailTemplateAttributeSubstitutionListener(
            \ArrayObject::class,
            'confirmationToken',
            'confirmationToken'
        );
        $this->record = new \ArrayObject();
    }

    public function testDeclaresTheConfiguredAttributeAsSubstitutable(): void
    {
        self::assertSame(
            [\ArrayObject::class => ['confirmationToken' => 'confirmationToken']],
            $this->listener->getSubstitutableEmailTemplateAttributes()
        );
    }

    public function testDeclaresTheTemplateParameterTheAttributeIsSubstitutedFrom(): void
    {
        $listener = new EmailTemplateAttributeSubstitutionListener(
            \ArrayObject::class,
            'newEmailVerificationCode',
            'emailVerificationCode'
        );

        self::assertSame(
            [\ArrayObject::class => ['newEmailVerificationCode' => 'emailVerificationCode']],
            $listener->getSubstitutableEmailTemplateAttributes()
        );
    }

    public function testSubstitutesTheParameterPassedForTheRender(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayObject::class,
                'confirmationToken'
            ),
            object: $this->record,
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => self::VALUE]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertTrue($event->isHandled());
        self::assertSame(self::VALUE, $event->getValue());
    }

    public function testSubstitutesOnEveryRecordOfTheConfiguredClass(): void
    {
        $anotherRecord = new \ArrayObject();
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayObject::class,
                'confirmationToken'
            ),
            object: $anotherRecord,
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['entity' => $this->record, 'confirmationToken' => self::VALUE]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertSame(self::VALUE, $event->getValue());
    }

    public function testSubstitutesOnADescendantOfTheConfiguredClass(): void
    {
        $listener = new EmailTemplateAttributeSubstitutionListener(
            \Traversable::class,
            'confirmationToken',
            'confirmationToken'
        );
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayIterator::class,
                'confirmationToken'
            ),
            object: new \ArrayIterator(),
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => self::VALUE]
        );

        $listener->onSecurityPolicyViolation($event);

        self::assertSame(self::VALUE, $event->getValue());
    }

    public function testSubstitutesNullWhenTheParameterIsNull(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayObject::class,
                'confirmationToken'
            ),
            object: $this->record,
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => null]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertTrue($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testReadsTheParameterUnderItsConfiguredName(): void
    {
        $listener = new EmailTemplateAttributeSubstitutionListener(
            \ArrayObject::class,
            'newEmailVerificationCode',
            'emailVerificationCode'
        );
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "newEmailVerificationCode" property is not allowed',
                \ArrayObject::class,
                'newEmailVerificationCode'
            ),
            object: $this->record,
            item: 'newEmailVerificationCode',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['emailVerificationCode' => self::VALUE]
        );

        $listener->onSecurityPolicyViolation($event);

        self::assertSame(self::VALUE, $event->getValue());
    }

    public function testSkipsAnotherAttribute(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "password" property is not allowed',
                \ArrayObject::class,
                'password'
            ),
            object: $this->record,
            item: 'password',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => self::VALUE]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertFalse($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testSkipsARecordOfAnotherClass(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \stdClass::class,
                'confirmationToken'
            ),
            object: new \stdClass(),
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => self::VALUE]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertFalse($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testSkipsWhenTheParameterWasNotPassed(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayObject::class,
                'confirmationToken'
            ),
            object: $this->record,
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['entity' => $this->record]
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertFalse($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testSkipsAViolationAnotherListenerAlreadyHandled(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedPropertyError(
                'Accessing "confirmationToken" property is not allowed',
                \ArrayObject::class,
                'confirmationToken'
            ),
            object: $this->record,
            item: 'confirmationToken',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 1,
            context: ['confirmationToken' => self::VALUE]
        );
        $event->setValue('already-substituted');

        $this->listener->onSecurityPolicyViolation($event);

        self::assertSame('already-substituted', $event->getValue());
    }
}
