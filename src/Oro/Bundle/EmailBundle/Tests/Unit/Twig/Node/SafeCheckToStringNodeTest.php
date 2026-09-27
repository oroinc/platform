<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\EmailBundle\Event\EmailTemplateSecurityPolicyViolationEvent;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\SecurityPolicyThrowingRuntimeErrorStub;
use Oro\Bundle\EmailBundle\Tests\Unit\Stub\StringableStub;
use Oro\Bundle\EmailBundle\Twig\Node\SafeCheckToStringNode;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Compiler;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Sandbox\SecurityPolicyInterface;
use Twig\Source;
use Twig\Template;

final class SafeCheckToStringNodeTest extends TestCase
{
    private const int LINENO = 42;
    private const string SUBSTITUTED_VALUE = 'REDACTED';
    private const array TWIG_CONTEXT = ['confirmationToken' => 'secret-token'];

    private Environment $env;
    private SandboxExtension $sandbox;
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

        $safeGetAttributeNodeExtension = new SafeGetAttributeNodeExtension();
        $safeGetAttributeNodeExtension->setEventDispatcher($this->eventDispatcher);

        $this->sandbox = self::createSandboxExtension(new SecurityPolicy([], [], [], [], []));
        $this->env = self::createEnvironment($this->sandbox);
        $this->env->addExtension($safeGetAttributeNodeExtension);

        $this->source = new Source('', 'test');
    }

    /**
     * Creates a sandbox extension that is enabled for every template source.
     */
    private static function createSandboxExtension(SecurityPolicyInterface $securityPolicy): SandboxExtension
    {
        return new SandboxExtension($securityPolicy, true);
    }

    private static function createEnvironment(SandboxExtension $sandbox): Environment
    {
        $env = new Environment(new ArrayLoader([]), ['strict_variables' => true]);
        $env->addExtension($sandbox);

        return $env;
    }

    public function testCompileEmitsCallToOwnRuntimeMethod(): void
    {
        $node = new SafeCheckToStringNode(new ConstantExpression('sample', self::LINENO));

        $compiler = new Compiler($this->env);
        $compiler->compile($node);

        self::assertSame(
            SafeCheckToStringNode::class
            . '::ensureToStringAllowed($this->env, $this->sandbox, "sample", 42, $this->source, false, $context)',
            $compiler->getSource()
        );
    }

    public function testCompileEmitsSpreadFlagWhenSpreadIsEnabled(): void
    {
        $node = new SafeCheckToStringNode(new ConstantExpression('sample', self::LINENO), true);

        $compiler = new Compiler($this->env);
        $compiler->compile($node);

        self::assertSame(
            SafeCheckToStringNode::class
            . '::ensureToStringAllowed($this->env, $this->sandbox, "sample", 42, $this->source, true, $context)',
            $compiler->getSource()
        );
    }

    public function testEnsureToStringAllowedReturnsOperandUntouchedWhenCoercionIsAllowed(): void
    {
        $object = new StringableStub();
        $sandbox = self::createSandboxExtension(
            new SecurityPolicy([], [], [StringableStub::class => ['__toString']], [], [])
        );

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            self::createEnvironment($sandbox),
            $sandbox,
            $object,
            self::LINENO,
            $this->source
        );

        self::assertSame($object, $result);
        self::assertEmpty($this->dispatchedEvents);
    }

    public function testEnsureToStringAllowedDispatchesEventAndReturnsNullWhenCoercionIsDenied(): void
    {
        $object = new StringableStub();

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $this->sandbox,
            $object,
            self::LINENO,
            $this->source,
            false,
            self::TWIG_CONTEXT
        );

        self::assertNull($result);
        self::assertCount(1, $this->dispatchedEvents);

        $event = $this->dispatchedEvents[0];
        self::assertInstanceOf(SecurityNotAllowedMethodError::class, $event->getViolation());
        self::assertSame($object, $event->getObject());
        self::assertSame('__toString', $event->getItem());
        self::assertSame([], $event->getArguments());
        self::assertSame(Template::METHOD_CALL, $event->getType());
        self::assertFalse($event->isDefinedTest());
        self::assertSame($this->source, $event->getSource());
        self::assertSame(self::LINENO, $event->getLineno());
        self::assertSame(self::TWIG_CONTEXT, $event->getContext());
        self::assertFalse($event->isHandled());
        self::assertNull($event->getValue());
    }

    public function testEnsureToStringAllowedReturnsValueSubstitutedByListener(): void
    {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue(self::SUBSTITUTED_VALUE)
        );

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $this->sandbox,
            new StringableStub(),
            self::LINENO,
            $this->source
        );

        self::assertSame(self::SUBSTITUTED_VALUE, $result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertTrue($this->dispatchedEvents[0]->isHandled());
    }

    public function testEnsureToStringAllowedReturnsNullAndDispatchesNothingWhenExtensionIsNotRegistered(): void
    {
        $result = SafeCheckToStringNode::ensureToStringAllowed(
            self::createEnvironment($this->sandbox),
            $this->sandbox,
            new StringableStub(),
            self::LINENO,
            $this->source
        );

        self::assertNull($result);
        self::assertEmpty($this->dispatchedEvents);
    }

    public function testEnsureToStringAllowedReturnsOperandUntouchedWhenSpreadCoercionIsAllowed(): void
    {
        $operand = [new StringableStub()];
        $sandbox = self::createSandboxExtension(
            new SecurityPolicy([], [], [StringableStub::class => ['__toString']], [], [])
        );

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            self::createEnvironment($sandbox),
            $sandbox,
            $operand,
            self::LINENO,
            $this->source,
            true
        );

        self::assertSame($operand, $result);
        self::assertEmpty($this->dispatchedEvents);
    }

    /**
     * The whole spread operand is substituted, not just the element that was denied, because the sandbox reports
     * the denial without telling which element caused it.
     */
    public function testEnsureToStringAllowedReturnsEmptyArrayWhenSpreadCoercionIsDenied(): void
    {
        $operand = [new StringableStub()];

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $this->sandbox,
            $operand,
            self::LINENO,
            $this->source,
            true
        );

        self::assertSame([], $result);
        self::assertCount(1, $this->dispatchedEvents);
        self::assertSame($operand, $this->dispatchedEvents[0]->getObject());
        self::assertSame([], $this->dispatchedEvents[0]->getContext());
    }

    public function testEnsureToStringAllowedReturnsIterableSubstitutedByListenerForSpreadCoercion(): void
    {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue([self::SUBSTITUTED_VALUE])
        );

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $this->sandbox,
            [new StringableStub()],
            self::LINENO,
            $this->source,
            true
        );

        self::assertSame([self::SUBSTITUTED_VALUE], $result);
    }

    /**
     * A scalar substitute would be a fatal error at the unpacking site, so it is dropped in favour of an empty array.
     *
     * @dataProvider nonIterableSubstituteDataProvider
     */
    public function testEnsureToStringAllowedDropsNonIterableSubstituteForSpreadCoercion(mixed $substitute): void
    {
        $this->eventDispatcher->addListener(
            EmailTemplateSecurityPolicyViolationEvent::class,
            static fn (EmailTemplateSecurityPolicyViolationEvent $event) => $event->setValue($substitute)
        );

        $result = SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $this->sandbox,
            [new StringableStub()],
            self::LINENO,
            $this->source,
            true
        );

        self::assertSame([], $result);
    }

    public static function nonIterableSubstituteDataProvider(): iterable
    {
        yield 'string' => ['substitute' => self::SUBSTITUTED_VALUE];
        yield 'integer' => ['substitute' => 42];
        yield 'null' => ['substitute' => null];
        yield 'object' => ['substitute' => new StringableStub()];
    }

    public function testEnsureToStringAllowedPropagatesErrorThatIsNotMethodDenial(): void
    {
        $sandbox = self::createSandboxExtension(new SecurityPolicyThrowingRuntimeErrorStub());

        $this->expectException(RuntimeError::class);
        $this->expectExceptionMessage(SecurityPolicyThrowingRuntimeErrorStub::ERROR_MESSAGE);

        SafeCheckToStringNode::ensureToStringAllowed(
            $this->env,
            $sandbox,
            new StringableStub(),
            self::LINENO,
            $this->source
        );
    }
}
