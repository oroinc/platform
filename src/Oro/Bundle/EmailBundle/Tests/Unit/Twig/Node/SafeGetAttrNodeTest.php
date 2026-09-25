<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\SandboxedObjectStub;
use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Oro\Bundle\EntityExtendBundle\Twig\NodeVisitor\GetAttrNodeVisitor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityNotAllowedPropertyError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

final class SafeGetAttrNodeTest extends TestCase
{
    private const int LINENO = 42;
    private const string SUBSTITUTED_VALUE = 'REDACTED';
    private const array TWIG_CONTEXT = ['confirmationToken' => 'secret-token'];

    private Environment $env;
    private Source $source;
    private EventDispatcher $eventDispatcher;

    /**
     * @var EmailTemplateSecurityPolicyViolationEvent[]
     */
    private array $dispatchedEvents = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = new EventDispatcher();
        $this->dispatchedEvents = [];
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            function (EmailTemplateSecurityPolicyViolationEvent $event) {
                $this->dispatchedEvents[] = $event;
            }
        );

        $this->env = self::createEnvironment();
        $this->env->addExtension($this->createSafeGetAttributeNodeExtension());

        $this->source = new Source('', 'test');
    }

    private function createSafeGetAttributeNodeExtension(): SafeGetAttributeNodeExtension
    {
        $safeGetAttributeNodeExtension = new SafeGetAttributeNodeExtension();
        $safeGetAttributeNodeExtension->setEventDispatcher($this->eventDispatcher);

        return $safeGetAttributeNodeExtension;
    }

    /**
     * Creates an environment whose security policy allows nothing - any method or property call on any object
     * is denied.
     */
    private static function createEnvironment(): Environment
    {
        $env = new Environment(new ArrayLoader([]), ['strict_variables' => true]);
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));

        return $env;
    }

    public function testAttributeDispatchesEventAndReturnsNullOnDeniedMethodAccess(): void
    {
        $object = new SandboxedObjectStub();

        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: $object,
            item: 'secret',
            arguments: ['arg'],
            type: 'method',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: true,
            lineno: self::LINENO,
            context: self::TWIG_CONTEXT
        );

        self::assertNull($result);
        self::assertCount(1, $this->dispatchedEvents);

        $event = $this->dispatchedEvents[0];
        self::assertInstanceOf(SecurityNotAllowedMethodError::class, $event->getViolation());
        self::assertSame($object, $event->getObject());
        self::assertSame('secret', $event->getItem());
        self::assertSame(['arg'], $event->getArguments());
        self::assertSame('method', $event->getType());
        self::assertFalse($event->isDefinedTest());
        self::assertSame($this->source, $event->getSource());
        self::assertSame(self::LINENO, $event->getLineno());
        self::assertSame(self::TWIG_CONTEXT, $event->getContext());
        self::assertFalse($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testAttributeDispatchesEventAndReturnsNullOnDeniedPropertyAccess(): void
    {
        $object = new SandboxedObjectStub();

        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: $object,
            item: 'secretProp',
            arguments: [],
            type: 'any',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertNull($result);
        self::assertCount(1, $this->dispatchedEvents);

        $event = $this->dispatchedEvents[0];
        self::assertInstanceOf(SecurityNotAllowedPropertyError::class, $event->getViolation());
        self::assertSame($object, $event->getObject());
        self::assertSame('secretProp', $event->getItem());
        self::assertSame('any', $event->getType());
        self::assertFalse($event->isDefinedTest());
        self::assertSame([], $event->getContext());
    }

    public function testAttributeReturnsValueSubstitutedByListener(): void
    {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue(self::SUBSTITUTED_VALUE)
        );

        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: new SandboxedObjectStub(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertSame(self::SUBSTITUTED_VALUE, $result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertTrue($this->dispatchedEvents[0]->isHandled());
    }

    /**
     * @dataProvider deniedAttributeDataProvider
     */
    public function testAttributeDispatchesEventAndReturnsFalseForDefinedTest(
        object $object,
        string $item,
        string $type
    ): void {
        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: $object,
            item: $item,
            arguments: [],
            type: $type,
            isDefinedTest: true,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertFalse($result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertTrue($this->dispatchedEvents[0]->isDefinedTest());
    }

    /**
     * @dataProvider deniedAttributeDataProvider
     */
    public function testAttributeReturnsTrueForDefinedTestWhenListenerSubstitutesValue(
        object $object,
        string $item,
        string $type
    ): void {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue(self::SUBSTITUTED_VALUE)
        );

        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: $object,
            item: $item,
            arguments: [],
            type: $type,
            isDefinedTest: true,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertTrue($result);
    }

    /**
     * @dataProvider deniedAttributeDataProvider
     */
    public function testAttributeReturnsFalseForDefinedTestWhenListenerSubstitutesNull(
        object $object,
        string $item,
        string $type
    ): void {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue(null)
        );

        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: $object,
            item: $item,
            arguments: [],
            type: $type,
            isDefinedTest: true,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertFalse($result);
    }

    public static function deniedAttributeDataProvider(): iterable
    {
        yield 'denied method' => ['object' => new SandboxedObjectStub(), 'item' => 'secret', 'type' => 'method'];
        yield 'denied property' => ['object' => new SandboxedObjectStub(), 'item' => 'secretProp', 'type' => 'any'];
    }

    public function testAttributeReturnsNullWhenExtensionIsNotRegistered(): void
    {
        $result = SafeGetAttrNode::attribute(
            env: self::createEnvironment(),
            source: $this->source,
            object: new SandboxedObjectStub(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertNull($result);
        self::assertEmpty($this->dispatchedEvents);
    }

    public function testAttributeReturnsValueOnAllowedMethodAccess(): void
    {
        $object = new SandboxedObjectStub();

        // The environment is built inline because the policy of createEnvironment() denies everything.
        $policy = new SecurityPolicy([], [], [SandboxedObjectStub::class => ['getAllowed']], [], []);
        $sandbox = new SandboxExtension($policy, true);
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension($sandbox);

        $result = SafeGetAttrNode::attribute(
            env: $env,
            source: $this->source,
            object: $object,
            item: 'getAllowed',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: true
        );

        self::assertSame('allowed-value', $result);
        self::assertEmpty($this->dispatchedEvents);
    }

    /**
     * The security policy of the environment denies everything, so a resolved value proves the sandbox check is
     * skipped altogether when the access is not sandboxed.
     */
    public function testAttributeReturnsValueAndDispatchesNothingWhenAccessIsNotSandboxed(): void
    {
        $result = SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: new SandboxedObjectStub(),
            item: 'secret',
            arguments: [],
            type: 'method',
            isDefinedTest: false,
            ignoreStrictCheck: false,
            sandboxed: false
        );

        self::assertSame('secret-value', $result);
        self::assertEmpty($this->dispatchedEvents);
    }

    /**
     * The context the event carries is everything the template could see where the denied access is: the parameters
     * it was rendered with, a {% set %} value and the loop variable alike.
     */
    public function testAttributeCarriesTheTwigContextTheTemplateCouldSeeWhenRenderingATemplate(): void
    {
        $orderLineItem = new SandboxedObjectStub();
        $env = new Environment(
            new ArrayLoader([
                'sample' => '{% set greeting = "Hello" %}'
                    . '{% for orderLineItem in orderLineItems %}{{ orderLineItem.secret }}{% endfor %}',
            ]),
            ['strict_variables' => true, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy(['set', 'for'], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());
        $env->addExtension($this->createSafeGetAttributeNodeExtension());

        $env->render('sample', ['orderLineItems' => [$orderLineItem]] + self::TWIG_CONTEXT);

        self::assertCount(1, $this->dispatchedEvents);

        $context = $this->dispatchedEvents[0]->getContext();
        self::assertSame('Hello', $context['greeting']);
        self::assertSame($orderLineItem, $context['orderLineItem']);
        self::assertSame(self::TWIG_CONTEXT['confirmationToken'], $context['confirmationToken']);
    }

    public function testAttributePropagatesNonSecurityErrors(): void
    {
        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage('Impossible to access an attribute ("field") on a null variable in "test".');

        SafeGetAttrNode::attribute(
            env: $this->env,
            source: $this->source,
            object: null,
            item: 'field'
        );
    }
}
