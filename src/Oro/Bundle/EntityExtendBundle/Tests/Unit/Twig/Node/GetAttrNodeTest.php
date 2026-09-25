<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Stub\SandboxedObjectStub;
use Oro\Bundle\EntityExtendBundle\Twig\Node\GetAttrNode;
use Oro\Bundle\EntityExtendBundle\Twig\NodeVisitor\GetAttrNodeVisitor;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Sandbox\SecurityNotAllowedMethodError;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;

/**
 * Every test builds an environment that compiles attribute accesses into {@see GetAttrNode} calls, with the sandbox
 * enabled for every source and a security policy that allows nothing.
 */
final class GetAttrNodeTest extends TestCase
{
    private const string TEMPLATE_NAME = 'sample';

    public function testCompileEmitsTwigContextAsTheLastArgument(): void
    {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => '']),
            ['strict_variables' => true, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        $compiledSource = $env->compileSource(new Source('{{ record.name }}', self::TEMPLATE_NAME));

        self::assertStringContainsString(
            GetAttrNode::class . '::attribute($this->env, $this->source, ',
            $compiledSource
        );
        self::assertStringContainsString(
            ', "name", [], "any", false, false, true, 1, $context)',
            $compiledSource
        );
    }

    /**
     * The optimized array call emits the attribute call as the right-hand side of a ternary, so the context has to
     * land inside the call rather than after the closing parenthesis of the ternary.
     */
    public function testCompileEmitsTwigContextAsTheLastArgumentForOptimizedArrayCall(): void
    {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => '']),
            ['strict_variables' => false, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        self::assertStringContainsString(
            ' : ' . GetAttrNode::class . '::attribute($this->env, $this->source,'
            . ' ($context["items"] ?? null), "key", [], "array", false, false, true, 1, $context))',
            $env->compileSource(new Source('{{ items["key"] }}', self::TEMPLATE_NAME))
        );
    }

    /**
     * Renders through the compiled template to prove the emitted $context argument resolves at runtime: it is only
     * in scope because Twig compiles every expression into a method that takes it.
     *
     * @dataProvider renderedTemplateDataProvider
     */
    public function testCompiledTemplateRendersWithTheEmittedContextArgument(
        string $template,
        bool $strictVariables
    ): void {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => $template]),
            ['strict_variables' => $strictVariables, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        self::assertSame(
            'Doe',
            $env->render(self::TEMPLATE_NAME, ['record' => ['name' => 'Doe'], 'items' => ['key' => 'Doe']])
        );
    }

    public static function renderedTemplateDataProvider(): iterable
    {
        yield 'attribute access' => ['template' => '{{ record.name }}', 'strictVariables' => true];
        yield 'array access' => ['template' => '{{ items["key"] }}', 'strictVariables' => true];
        yield 'optimized array access' => ['template' => '{{ items["key"] }}', 'strictVariables' => false];
    }

    public function testAttributeThrowsWhenMethodAccessIsDeniedBySandbox(): void
    {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => '']),
            ['strict_variables' => true, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        $this->expectException(SecurityNotAllowedMethodError::class);
        $this->expectExceptionMessage(
            sprintf(
                'Calling "secret" method on a "%s" object is not allowed in "%s".',
                SandboxedObjectStub::class,
                self::TEMPLATE_NAME
            )
        );

        GetAttrNode::attribute(
            env: $env,
            source: new Source('', self::TEMPLATE_NAME),
            object: new SandboxedObjectStub(),
            item: 'secret',
            type: 'method',
            sandboxed: true
        );
    }

    public function testAttributeReturnsFalseForDefinedTestWhenMethodAccessIsDenied(): void
    {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => '']),
            ['strict_variables' => true, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        $result = GetAttrNode::attribute(
            env: $env,
            source: new Source('', self::TEMPLATE_NAME),
            object: new SandboxedObjectStub(),
            item: 'secret',
            type: 'method',
            isDefinedTest: true,
            sandboxed: true
        );

        self::assertFalse($result);
    }

    /**
     * The property denial is checked before the is-defined test, so the denial has to be answered with false rather
     * than raised as an error.
     */
    public function testAttributeReturnsFalseForDefinedTestWhenPropertyAccessIsDenied(): void
    {
        $env = new Environment(
            new ArrayLoader([self::TEMPLATE_NAME => '']),
            ['strict_variables' => true, 'autoescape' => false]
        );
        $env->addExtension(new SandboxExtension(new SecurityPolicy([], [], [], [], []), true));
        $env->addNodeVisitor(new GetAttrNodeVisitor());

        $result = GetAttrNode::attribute(
            env: $env,
            source: new Source('', self::TEMPLATE_NAME),
            object: new SandboxedObjectStub(),
            item: 'secretProp',
            type: 'any',
            isDefinedTest: true,
            sandboxed: true
        );

        self::assertFalse($result);
    }
}
