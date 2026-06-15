<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use Oro\Component\Testing\Logger\TestLogger;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

final class SafeGetAttrNodeTest extends TestCase
{
    private Environment $env;
    private Source $source;
    private TestLogger $logger;

    #[\Override]
    protected function setUp(): void
    {
        // Security policy that allows nothing - any method or property call on any object is denied.
        $securityPolicy = new SecurityPolicy([], [], [], [], []);
        $sandboxExtension = new SandboxExtension($securityPolicy, true);

        $this->logger = new TestLogger();
        $safeGetAttrNodeExtension = new SafeGetAttributeNodeExtension();
        $safeGetAttrNodeExtension->setLogger($this->logger);

        $this->env = new Environment(new ArrayLoader([]), ['strict_variables' => true]);
        $this->env->addExtension($sandboxExtension);
        $this->env->addExtension($safeGetAttrNodeExtension);

        $this->source = new Source('', 'test');
    }

    public function testAttributeReturnsNullOnDeniedMethodAccess(): void
    {
        $object = new class () {
            public function secret(): string
            {
                return 'secret-value';
            }
        };

        $result = SafeGetAttrNode::attribute(
            $this->env,
            $this->source,
            $object,
            'secret',
            [],
            'method',
            false,
            false,
            true
        );

        self::assertNull($result);
        self::assertTrue($this->logger->hasErrorRecords());
        self::assertStringContainsString(
            'Twig security policy exception caught during email template rendering: Calling "secret" method',
            $this->logger->records[0]['message']
        );
    }

    public function testAttributeReturnsTrueForDefinedTestOnDeniedMethodAccess(): void
    {
        $object = new class () {
            public function secret(): string
            {
                return 'secret-value';
            }
        };

        $result = SafeGetAttrNode::attribute(
            $this->env,
            $this->source,
            $object,
            'secret',
            [],
            'method',
            true,  // $isDefinedTest
            false,
            true
        );

        self::assertFalse($result);
    }

    public function testAttributeReturnsNullOnDeniedPropertyAccess(): void
    {
        $object = new class () {
            public string $secretProp = 'secret-value';
        };

        $result = SafeGetAttrNode::attribute(
            $this->env,
            $this->source,
            $object,
            'secretProp',
            [],
            'any',
            false,
            false,
            true
        );

        self::assertNull($result);
        self::assertTrue($this->logger->hasErrorRecords());
        self::assertStringContainsString(
            'Twig security policy exception caught during email template rendering: Calling "secretProp" property',
            $this->logger->records[0]['message']
        );
    }

    public function testAttributeReturnsTrueForDefinedTestOnDeniedPropertyAccess(): void
    {
        $object = new class () {
            public string $secretProp = 'secret-value';
        };

        $result = SafeGetAttrNode::attribute(
            $this->env,
            $this->source,
            $object,
            'secretProp',
            [],
            'any',
            true,  // $isDefinedTest
            false,
            true
        );

        self::assertFalse($result);
    }

    public function testAttributeReturnsValueOnAllowedMethodAccess(): void
    {
        $object = new class () {
            public function getAllowed(): string
            {
                return 'allowed-value';
            }
        };

        // Allow the method explicitly in the security policy.
        $policy = new SecurityPolicy([], [], [get_class($object) => ['getAllowed']], [], []);
        $sandbox = new SandboxExtension($policy, true);
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension($sandbox);

        $result = SafeGetAttrNode::attribute(
            $env,
            $this->source,
            $object,
            'getAllowed',
            [],
            'method',
            false,
            false,
            true
        );

        self::assertSame('allowed-value', $result);
    }

    public function testAttributePropagatesNonSecurityErrors(): void
    {
        $this->expectException(RuntimeError::class);

        SafeGetAttrNode::attribute(
            $this->env,
            $this->source,
            null,
            'field'
        );
    }
}
