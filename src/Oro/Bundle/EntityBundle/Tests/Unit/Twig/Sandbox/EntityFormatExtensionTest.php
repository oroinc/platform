<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Sandbox;

use Oro\Bundle\EntityBundle\Tests\Unit\Fixtures\Stub\SomeEntity;
use Oro\Bundle\EntityBundle\Twig\EntityExtension;
use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityFormatExtension;
use Oro\Bundle\UIBundle\Twig\FormatExtension;
use Oro\Bundle\UIBundle\Twig\HtmlTagExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Twig\Environment;
use Twig\Node\Node;
use Twig\TwigFunction;

class EntityFormatExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var Environment|\PHPUnit\Framework\MockObject\MockObject */
    private $environment;

    /** @var EntityExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $entityExtension;

    /** @var FormatExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $formatExtension;

    /** @var HtmlTagExtension|\PHPUnit\Framework\MockObject\MockObject */
    private $htmlTagExtension;

    /** @var EntityFormatExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->environment = $this->createMock(Environment::class);
        $this->entityExtension = $this->createMock(EntityExtension::class);
        $this->formatExtension = $this->createMock(FormatExtension::class);
        $this->htmlTagExtension = $this->createMock(HtmlTagExtension::class);

        $this->environment->expects(self::any())
            ->method('getExtension')
            ->willReturnMap([
                [EntityExtension::class, $this->entityExtension],
                [FormatExtension::class, $this->formatExtension],
                [HtmlTagExtension::class, $this->htmlTagExtension]
            ]);

        $this->extension = new EntityFormatExtension();
        $this->extension->setFormatters([
            \stdClass::class => [
                'field1' => 'formatter1',
                'field2' => ['formatter2'],
                'field3' => ['formatter3', ['param1' => 'val1']]
            ]
        ]);
    }

    public function testGetFunctions()
    {
        $functions = $this->extension->getFunctions();
        self::assertCount(1, $functions);

        /** @var TwigFunction $formatFunction */
        $formatFunction = $functions[0];
        self::assertInstanceOf(TwigFunction::class, $formatFunction);
        self::assertEquals('_entity_var', $formatFunction->getName());
        self::assertTrue($formatFunction->needsEnvironment());
        self::assertEquals(['html'], $formatFunction->getSafe($this->createMock(Node::class)));
    }

    public function testGetSafeFormatExpressionWithoutNotDefinedMessage()
    {
        self::assertEquals(
            '{% if entity.someEntity.field1 is defined %}'
            . '{{ _entity_var("field1", entity.someEntity.field1, entity.someEntity) }}'
            . '{% endif %}',
            $this->extension->getSafeFormatExpression(
                'field1',
                'entity.someEntity.field1',
                'entity.someEntity'
            )
        );
    }

    public function testGetSafeFormatExpressionWithNotDefinedMessage()
    {
        self::assertEquals(
            '{% if entity.someEntity.field1 is defined %}'
            . '{{ _entity_var("field1", entity.someEntity.field1, entity.someEntity) }}'
            . '{% else %}'
            . '{{ "value for \"someEntity.field1\" not found" }}'
            . '{% endif %}',
            $this->extension->getSafeFormatExpression(
                'field1',
                'entity.someEntity.field1',
                'entity.someEntity',
                'value for "someEntity.field1" not found'
            )
        );
    }

    public function testFormatForValueWithFormatterAsString()
    {
        $value = 'testValue';
        $formattedValue = 'formattedValue';

        $this->formatExtension->expects(self::once())
            ->method('format')
            ->with($value, 'formatter1', [])
            ->willReturn($formattedValue);

        self::assertSame(
            $formattedValue,
            self::callTwigFunction(
                $this->extension,
                '_entity_var',
                [
                    $this->environment,
                    'field1',
                    $value,
                    new \stdClass()
                ]
            )
        );
    }

    public function testFormatForValueWithFormatterAsArrayButWithoutArguments()
    {
        $value = 'testValue';
        $formattedValue = 'formattedValue';

        $this->formatExtension->expects(self::once())
            ->method('format')
            ->with($value, 'formatter2', [])
            ->willReturn($formattedValue);

        self::assertSame(
            $formattedValue,
            self::callTwigFunction(
                $this->extension,
                '_entity_var',
                [
                    $this->environment,
                    'field2',
                    $value,
                    new \stdClass()
                ]
            )
        );
    }

    public function testFormatForValueWithFormatterAsArrayWithArguments()
    {
        $value = 'testValue';
        $formattedValue = 'formattedValue';

        $this->formatExtension->expects(self::once())
            ->method('format')
            ->with($value, 'formatter3', ['param1' => 'val1'])
            ->willReturn($formattedValue);

        self::assertSame(
            $formattedValue,
            self::callTwigFunction(
                $this->extension,
                '_entity_var',
                [
                    $this->environment,
                    'field3',
                    $value,
                    new \stdClass()
                ]
            )
        );
    }

    public function testFormatForObjectValueWithoutFormatter()
    {
        $value = new SomeEntity();
        $formattedValue = 'formattedEntityName';

        $this->entityExtension->expects(self::once())
            ->method('getEntityName')
            ->with(self::identicalTo($value))
            ->willReturn($formattedValue);

        self::assertSame(
            $formattedValue,
            self::callTwigFunction(
                $this->extension,
                '_entity_var',
                [
                    $this->environment,
                    'anotherField',
                    $value,
                    new \stdClass()
                ]
            )
        );
    }

    public function testFormatForScalarValueWithoutFormatter()
    {
        $value = 'testValue';
        $formattedValue = 'htmlSanitizeValue';

        $this->htmlTagExtension->expects(self::once())
            ->method('htmlSanitize')
            ->with($value)
            ->willReturn($formattedValue);

        self::assertSame(
            $formattedValue,
            self::callTwigFunction(
                $this->extension,
                '_entity_var',
                [
                    $this->environment,
                    'anotherField',
                    $value,
                    new \stdClass()
                ]
            )
        );
    }
}
