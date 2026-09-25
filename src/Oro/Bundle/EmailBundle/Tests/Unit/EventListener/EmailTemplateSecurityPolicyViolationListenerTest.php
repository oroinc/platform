<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\EventListener\EmailTemplateSecurityPolicyViolationListener;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\SecurityPolicyViolationEventForbiddingContextReadStub;
use Oro\Component\Testing\Logger\TestLogger;
use PHPUnit\Framework\TestCase;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Source;

final class EmailTemplateSecurityPolicyViolationListenerTest extends TestCase
{
    private const array TWIG_CONTEXT = [
        'confirmationToken' => 'secret-confirmation-token',
        'emailVerificationCode' => 'secret-verification-code',
    ];

    private TestLogger $logger;
    private EmailTemplateSecurityPolicyViolationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = new TestLogger();
        $this->listener = new EmailTemplateSecurityPolicyViolationListener($this->logger);
    }

    public function testLogsTheViolation(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedMethodError(
                'Calling "secret" method is not allowed',
                'SomeClass',
                'secret'
            ),
            object: new \stdClass(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 42,
            context: self::TWIG_CONTEXT
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertTrue($this->logger->hasErrorRecords());
        self::assertSame(
            'Twig security policy exception caught during email template rendering:'
            . ' Calling "secret" method is not allowed',
            $this->logger->records[0]['message']
        );
        self::assertSame($event->getViolation(), $this->logger->records[0]['context']['exception']);
    }

    /**
     * The Twig context carries the template parameters, and a password reset confirmation token is one of them,
     * so nothing taken from it may reach the log.
     */
    public function testLogsNoValueTakenFromTheTwigContext(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedMethodError(
                'Calling "secret" method is not allowed',
                'SomeClass',
                'secret'
            ),
            object: new \stdClass(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 42,
            context: self::TWIG_CONTEXT
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertCount(1, $this->logger->records);
        self::assertSame(['exception'], array_keys($this->logger->records[0]['context']));
        self::assertSame($event->getViolation(), $this->logger->records[0]['context']['exception']);
        self::assertStringNotContainsString(
            self::TWIG_CONTEXT['confirmationToken'],
            $this->logger->records[0]['message']
        );
        self::assertStringNotContainsString(
            self::TWIG_CONTEXT['emailVerificationCode'],
            $this->logger->records[0]['message']
        );
    }

    /**
     * Reading the context at all is the step that puts a secret within reach of the log, so the listener must not
     * even take it from the event.
     */
    public function testDoesNotReadTheTwigContext(): void
    {
        $event = new SecurityPolicyViolationEventForbiddingContextReadStub(
            violation: new SecurityNotAllowedMethodError(
                'Calling "secret" method is not allowed',
                'SomeClass',
                'secret'
            ),
            object: new \stdClass(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 42,
            context: self::TWIG_CONTEXT
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertCount(1, $this->logger->records);
    }

    public function testDoesNotLogWhenAnotherListenerAlreadyHandledTheViolation(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedMethodError(
                'Calling "secret" method is not allowed',
                'SomeClass',
                'secret'
            ),
            object: new \stdClass(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            source: new Source('', 'sample_template'),
            lineno: 42,
            context: self::TWIG_CONTEXT
        );
        $event->setValue('REDACTED');

        $this->listener->onSecurityPolicyViolation($event);

        self::assertEmpty($this->logger->records);
    }

    public function testDoesNotLogForDefinedTest(): void
    {
        $event = new EmailTemplateSecurityPolicyViolationEvent(
            violation: new SecurityNotAllowedMethodError(
                'Calling "secret" method is not allowed',
                'SomeClass',
                'secret'
            ),
            object: new \stdClass(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: true,
            source: new Source('', 'sample_template'),
            lineno: 42,
            context: self::TWIG_CONTEXT
        );

        $this->listener->onSecurityPolicyViolation($event);

        self::assertEmpty($this->logger->records);
    }
}
