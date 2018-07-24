<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Twig;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Twig\LayoutExtension;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\Form\FormView;

class LayoutExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var TwigRendererInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $renderer;

    /** @var TextHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $textHelper;

    /** @var LayoutExtension */
    protected $extension;

    protected function setUp()
    {
        $this->renderer = $this->createMock(TwigRendererInterface::class);
        $this->textHelper = $this->getMockBuilder(TextHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_layout.twig.renderer', $this->renderer)
            ->add('oro_layout.text.helper', $this->textHelper)
            ->getContainer($this);

        $this->extension = new LayoutExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals('layout', $this->extension->getName());
    }

    public function testInitRuntime()
    {
        /** @var \Twig_Environment $environment */
        $environment = $this->getMockBuilder('\Twig_Environment')
            ->getMock();

        $this->renderer->expects($this->once())
            ->method('setEnvironment')
            ->with($this->identicalTo($environment));

        $this->extension->initRuntime($environment);
        self::assertSame($this->renderer, $this->extension->renderer);
    }

    public function testGetTokenParsers()
    {
        $tokenParsers = $this->extension->getTokenParsers();

        $this->assertCount(1, $tokenParsers);

        $this->assertInstanceOf(
            'Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser',
            $tokenParsers[0]
        );
    }

    public function testMergeContext()
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

        /** @var BlockView $view */
        foreach ([$parent, $firstChild, $secondChild] as $view) {
            $this->assertArrayHasKey($name, $view->vars);
            $this->assertEquals($value, $view->vars[$name]);
        }
    }

    /**
     * @param array $attr
     * @param array $defaultAttr
     * @param array $expected
     *
     * @dataProvider attributeProvider
     */
    public function testDefaultAttributes($attr, $defaultAttr, $expected)
    {
        $this->assertEquals(
            $expected,
            self::callTwigFunction($this->extension, 'layout_attr_defaults', [$attr, $defaultAttr])
        );
    }

    /**
     * @return array
     */
    public function attributeProvider()
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

    public function testSetClassPrefixToForm()
    {
        $prototypeView = $this->createMock(FormView::class);

        $childView = $this->createMock(FormView::class);
        $childView->vars['prototype'] = $prototypeView;

        $formView = $this->createMock(FormView::class);
        $formView->children = [$childView];

        $this->extension->setClassPrefixToForm($formView, 'foo');

        $this->assertEquals($formView->vars['class_prefix'], 'foo');
        $this->assertEquals($childView->vars['class_prefix'], 'foo');
        $this->assertEquals($prototypeView->vars['class_prefix'], 'foo');
    }

    /**
     * @dataProvider convertValueToStringDataProvider
     * @param $value
     * @param $expectedConvertedValue
     */
    public function testConvertValueToString($value, $expectedConvertedValue)
    {
        $this->assertSame($expectedConvertedValue, $this->extension->convertValueToString($value));
    }

    /**
     * @return array
     */
    public function convertValueToStringDataProvider()
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
}
