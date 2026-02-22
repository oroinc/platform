<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\LayoutBundle\Layout\Context\LayoutContextStack;
use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;
use Oro\Bundle\ThemeBundle\Provider\ThemeConfigurationProvider as GeneralThemeConfigurationProvider;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;

class LayoutExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private TextHelper&MockObject $textHelper;
    private LayoutContextStack&MockObject $layoutContextStack;
    private GeneralThemeConfigurationProvider&MockObject $generalThemeConfigurationProvider;
    private LayoutExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->textHelper = $this->createMock(TextHelper::class);
        $this->layoutContextStack = $this->createMock(LayoutContextStack::class);
        $this->generalThemeConfigurationProvider = $this->createMock(GeneralThemeConfigurationProvider::class);

        $container = self::getContainerBuilder()
            ->add(TextHelper::class, $this->textHelper)
            ->add(LayoutContextStack::class, $this->layoutContextStack)
            ->add(Inflector::class, (new InflectorFactory())->build())
            ->add(GeneralThemeConfigurationProvider::class, $this->generalThemeConfigurationProvider)
            ->getContainer($this);

        $this->extension = new LayoutExtension($container);
    }

    public function testGetTokenParsers(): void
    {
        $tokenParsers = $this->extension->getTokenParsers();

        $this->assertCount(1, $tokenParsers);

        $this->assertInstanceOf(BlockThemeTokenParser::class, $tokenParsers[0]);
    }

    public function testMergeContext(): void
    {
        $parent = new BlockView();
        $firstChild = new BlockView();
        $secondChild = new BlockView();

        $parent->children['first'] = $firstChild;
        $parent->children['second'] = $secondChild;

        $name = 'name';
        $value = 'value';

        $this->assertEquals(
            $parent,
            self::callTwigFilter($this->extension, 'merge_context', [$parent, [$name => $value]])
        );

        foreach ([$parent, $firstChild, $secondChild] as $view) {
            $this->assertArrayHasKey($name, $view->vars);
            $this->assertEquals($value, $view->vars[$name]);
        }
    }

    /**
     * @dataProvider attributeProvider
     */
    public function testDefaultAttributes(array $attr, array $defaultAttr, array $expected): void
    {
        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'layout_attr_defaults', [$attr, $defaultAttr])
        );
    }

    public function attributeProvider(): array
    {
        return [
            'attributes with tilde' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => 'testClass'
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ' input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'testClass input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes with array' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => 'test'
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ['class' => ' input input_block']
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => ['test', 'class' => ' input input_block'],
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes with array of arrays' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                    'class' => ['class_prefixes' => ['mobile']]
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    '~class' => ['class' => ' input input_block', 'class_prefixes' => ['web']]
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => ['class' => ' input input_block', 'class_prefixes' => ['web', 'mobile']],
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes without tilde' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    'class' => 'input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
            'attributes default' => [
                'attr'  => [
                    'id' => 'someId',
                    'name' => 'test',
                ],
                'defaultAttr'   => [
                    'autofocus' => true,
                    'name' => 'default_value',
                    'class' => 'input input_block'
                ],
                'expected'  => [
                    'autofocus' => true,
                    'class' => 'input input_block',
                    'id' => 'someId',
                    'name' => 'test',
                ],
            ],
        ];
    }

    public function testSetClassPrefixToForm(): void
    {
        $prototypeView = $this->createMock(FormView::class);

        $childView = $this->createMock(FormView::class);
        $childView->vars['prototype'] = $prototypeView;

        $formView = $this->createMock(FormView::class);
        $formView->children = [$childView];

        self::callTwigFunction($this->extension, 'set_class_prefix_to_form', [$formView, 'foo']);

        $this->assertEquals('foo', $formView->vars['class_prefix']);
        $this->assertEquals('foo', $childView->vars['class_prefix']);
        $this->assertEquals('foo', $prototypeView->vars['class_prefix']);
    }

    /**
     * @dataProvider convertValueToStringDataProvider
     */
    public function testConvertValueToString(mixed $value, string $expectedConvertedValue): void
    {
        $this->assertSame(
            $expectedConvertedValue,
            self::callTwigFunction($this->extension, 'convert_value_to_string', [$value])
        );
    }

    public function convertValueToStringDataProvider(): array
    {
        return [
            'object conversion' => [
                new \stdClass(),
                'stdClass'
            ],
            'array conversion'  => [
                ['Foo', 'Bar'],
                '["Foo","Bar"]'
            ],
            'null conversion' => [
                null,
                'NULL'
            ],
            'string' => [
                'some string',
                'some string'
            ]
        ];
    }

    public function testCloneFormViewWithUniqueId(): void
    {
        $formView = new FormView();
        $formView->vars['id'] = 'root-view';
        $formView->vars['additionalField'] = 'value1';
        $childFormView = new FormView($formView);
        $childFormView->vars['id'] = 'child-view';
        $childFormView->vars['extraField'] = 'value2';
        $formView->children['child'] = $childFormView;

        $newFormView = self::callTwigFunction($this->extension, 'clone_form_view_with_unique_id', [$formView, 'foo']);

        $formView->setRendered();
        $formView->offsetGet('child')->setRendered();

        $this->assertEquals('root-view-foo', $newFormView->vars['id']);
        $this->assertEquals('value1', $newFormView->vars['additionalField']);
        $this->assertFalse($newFormView->isRendered());
        $this->assertEquals('child-view-foo', $newFormView->offsetGet('child')->vars['id']);
        $this->assertEquals('value2', $newFormView->offsetGet('child')->vars['extraField']);
        $this->assertEquals($newFormView, $newFormView->offsetGet('child')->parent);
        $this->assertFalse($newFormView->offsetGet('child')->isRendered());
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeConfigurationOption(mixed $expectedOptionValue): void
    {
        $option = 'some_option';

        $this->generalThemeConfigurationProvider->expects(self::once())
            ->method('getThemeConfigurationOption')
            ->with($option)
            ->willReturn($expectedOptionValue);

        self::assertEquals(
            $expectedOptionValue,
            self::callTwigFunction($this->extension, 'oro_theme_configuration_value', [$option])
        );
    }

    /**
     * @dataProvider getThemeConfigurationOptionDataProvider
     */
    public function testGetThemeDefinitionValue(mixed $expectedValue): void
    {
        $key = 'some_value';

        $this->generalThemeConfigurationProvider->expects(self::once())
            ->method('getThemeProperty')
            ->with($key)
            ->willReturn($expectedValue);

        self::assertEquals(
            $expectedValue,
            self::callTwigFunction($this->extension, 'oro_theme_definition_value', [$key])
        );
    }

    public function getThemeConfigurationOptionDataProvider(): array
    {
        return [
            [null],
            ['some_option_value'],
            [123],
            [123.321],
            [false],
            [['foo' => 'bar']],
            [new \stdClass()],
        ];
    }
}
