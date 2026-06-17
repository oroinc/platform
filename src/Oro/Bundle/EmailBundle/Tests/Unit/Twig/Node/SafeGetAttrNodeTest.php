<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Unit\Twig\Node;

use Oro\Bundle\EmailBundle\Tests\Unit\Stub\TwigObjectStub;
use Oro\Bundle\EmailBundle\Twig\Node\SafeGetAttrNode;
use Oro\Bundle\EmailBundle\Twig\SafeGetAttributeNodeExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Twig\Compiler;
use Twig\Environment;
use Twig\Extension\SandboxExtension;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ConstantExpression;
use Twig\Sandbox\SecurityPolicy;
use Twig\Source;
use Twig\Template;

final class SafeGetAttrNodeTest extends TestCase
{
    public function testConstructorStoresNodesAndAttributes(): void
    {
        $nodeNode = new ConstantExpression('obj', 1);
        $attributeNode = new ConstantExpression('field', 1);

        $node = new SafeGetAttrNode(
            ['node' => $nodeNode, 'attribute' => $attributeNode],
            ['type' => Template::ANY_CALL, 'ignore_strict_check' => false, 'optimizable' => false],
            42,
            'some_tag'
        );

        self::assertSame($nodeNode, $node->getNode('node'));
        self::assertSame($attributeNode, $node->getNode('attribute'));
        self::assertSame(Template::ANY_CALL, $node->getAttribute('type'));
        self::assertFalse($node->getAttribute('ignore_strict_check'));
        self::assertFalse($node->getAttribute('optimizable'));
        self::assertSame(42, $node->getTemplateLine());
    }

    public function testConstructorDoesNotEnableDefinedTestByDefault(): void
    {
        $node = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            ['type' => Template::ANY_CALL, 'ignore_strict_check' => false, 'optimizable' => false],
            1
        );

        self::assertFalse($node->isDefinedTestEnabled());
    }

    public function testConstructorEnablesDefinedTestWhenIsDefinedTestAttributeIsTrue(): void
    {
        $node = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            [
                'type' => Template::ANY_CALL,
                'ignore_strict_check' => false,
                'optimizable' => false,
                'is_defined_test' => true,
            ],
            1
        );

        self::assertTrue($node->isDefinedTestEnabled());
    }

    public function testConstructorDoesNotEnableDefinedTestWhenIsDefinedTestAttributeIsFalse(): void
    {
        $node = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            [
                'type' => Template::ANY_CALL,
                'ignore_strict_check' => false,
                'optimizable' => false,
                'is_defined_test' => false,
            ],
            1
        );

        self::assertFalse($node->isDefinedTestEnabled());
    }

    public function testCompileReplacesCoreExtensionGetAttributeCallWithSafeGetAttributeCall(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $compiler = new Compiler($env);

        $node = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            [
                'type' => Template::ANY_CALL,
                'ignore_strict_check' => false,
                'optimizable' => false,
                'null_safe' => false,
            ],
            1
        );
        $node->compile($compiler);

        self::assertStringContainsString(SafeGetAttrNode::class . '::safeGetAttribute(', $compiler->getSource());
    }

    public function testCompileDoesNotContainOriginalCoreExtensionGetAttributeCall(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $compiler = new Compiler($env);

        $node = new SafeGetAttrNode(
            [
                'node' => new ConstantExpression('obj', 1),
                'attribute' => new ConstantExpression('field', 1),
            ],
            [
                'type' => Template::ANY_CALL,
                'ignore_strict_check' => false,
                'optimizable' => false,
                'null_safe' => false,
            ],
            1
        );
        $node->compile($compiler);

        self::assertStringNotContainsString('CoreExtension::getAttribute(', $compiler->getSource());
    }

    public function testSafeGetAttributeReturnsValueOnSuccessfulAccess(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $source = new Source('', 'test');
        $object = ['key' => 'expected_value'];

        $result = SafeGetAttrNode::safeGetAttribute(
            $env,
            $source,
            $object,
            'key',
            [],
            Template::ARRAY_CALL
        );

        self::assertSame('expected_value', $result);
    }

    /**
     * @dataProvider definedTestFlagForMethodErrorProvider
     */
    public function testSafeGetAttributeHandlesSecurityNotAllowedMethodError(
        bool $isDefinedTest,
        mixed $expectedResult
    ): void {
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension(new SandboxExtension(new SecurityPolicy(), true));
        $source = new Source('', 'test');
        $object = new TwigObjectStub();

        $result = SafeGetAttrNode::safeGetAttribute(
            $env,
            $source,
            $object,
            'getValue',
            [],
            Template::METHOD_CALL,
            $isDefinedTest,
            false,
            true
        );

        self::assertSame($expectedResult, $result);
    }

    public static function definedTestFlagForMethodErrorProvider(): iterable
    {
        yield 'returns false when isDefinedTest is true' => [true, false];
        yield 'returns null when isDefinedTest is false' => [false, null];
    }

    /**
     * @dataProvider definedTestFlagForPropertyErrorProvider
     */
    public function testSafeGetAttributeHandlesSecurityNotAllowedPropertyError(
        bool $isDefinedTest,
        mixed $expectedResult
    ): void {
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension(new SandboxExtension(new SecurityPolicy(), true));
        $source = new Source('', 'test');
        $object = new TwigObjectStub();

        $result = SafeGetAttrNode::safeGetAttribute(
            $env,
            $source,
            $object,
            'propertyOnly',
            [],
            Template::ANY_CALL,
            $isDefinedTest,
            false,
            true
        );

        self::assertSame($expectedResult, $result);
    }

    public static function definedTestFlagForPropertyErrorProvider(): iterable
    {
        yield 'returns false when isDefinedTest is true' => [true, false];
        yield 'returns null when isDefinedTest is false' => [false, null];
    }

    public function testSafeGetAttributeLogsErrorWhenSafeGetAttributeNodeExtensionIsPresent(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension(new SandboxExtension(new SecurityPolicy(), true));
        $source = new Source('', 'test');
        $object = new TwigObjectStub();

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Twig security policy exception caught during email template rendering:'),
                self::arrayHasKey('exception')
            );

        $extension = new SafeGetAttributeNodeExtension();
        $extension->setLogger($logger);
        $env->addExtension($extension);

        SafeGetAttrNode::safeGetAttribute(
            $env,
            $source,
            $object,
            'getValue',
            [],
            Template::METHOD_CALL,
            false,
            false,
            true
        );
    }

    public function testSafeGetAttributeDoesNotLogWhenSafeGetAttributeNodeExtensionIsAbsent(): void
    {
        $env = new Environment(new ArrayLoader([]));
        $env->addExtension(new SandboxExtension(new SecurityPolicy(), true));
        $source = new Source('', 'test');
        $object = new TwigObjectStub();

        $result = SafeGetAttrNode::safeGetAttribute(
            $env,
            $source,
            $object,
            'getValue',
            [],
            Template::METHOD_CALL,
            false,
            false,
            true
        );

        self::assertNull($result);
    }
}
