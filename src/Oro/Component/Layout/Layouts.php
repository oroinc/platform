<?php

namespace Oro\Component\Layout;

use Oro\Component\ExpressionLanguage\ExpressionLanguage;
use Oro\Component\Layout\ExpressionLanguage\Encoder\ExpressionEncoderRegistry;
use Oro\Component\Layout\ExpressionLanguage\Encoder\JsonExpressionEncoder;
use Oro\Component\Layout\ExpressionLanguage\ExpressionManipulator;
use Oro\Component\Layout\ExpressionLanguage\ExpressionProcessor;
use Oro\Component\Layout\Extension\Core\CoreExtension;

/**
 * Entry point of the Layout component.
 *
 * Use this class to conveniently create new layout factories:
 *
 * <code>
 * $context = new LayoutContext();
 * $layoutFactory = Layouts::createLayoutFactory();
 * $layout = $layoutFactory->createLayoutBuilder()
 *     ->add('root', null, 'root')
 *     ->add('header', 'root', 'header')
 *     ->add('logo', 'header', 'logo', ['title' => 'Hello World!'])
 *     ->getLayout($context);
 * </code>
 *
 * You can also add custom extensions to the layout factory:
 *
 * <code>
 * $layoutFactory = Layouts::createLayoutFactoryBuilder()
 *     ->addExtension(new AcmeExtension())
 *     ->getLayoutFactory();
 * </code>
 *
 * If you create custom block types, block type extensions or
 * layout updates, it is generally recommended to create your own
 * extensions that lazily load these block types, block type extensions
 * and layout updates. In projects where performance does not matter
 * that much, you can also pass them directly to the layout factory:
 *
 * <code>
 * $layoutFactory = Layouts::createLayoutFactoryBuilder()
 *     ->addType(new MyType())
 *     ->addTypeExtension(new MyTypeExtension())
 *     ->addLayoutUpdate('my_item_id', new MyLayoutUpdate())
 *     ->getLayoutFactory();
 * </code>
 *
 * The Layout component renderer are based on Symfony's Form renderer.
 * The following example shows how to use Symfony's Templating component
 * for rendering. Note that a PhpEngine object is needed in this case.
 * Also you should pass the names of the default themes as the second
 * argument for TemplatingRendererEngine.
 * 'OroLayoutBundle:Layout/php'
 *
 * <code>
 * $formRenderer = new FormRenderer(
 *     new TemplatingRendererEngine($engine, ['OroLayoutBundle:Layout/php'])
 * );
 * $engine->addHelpers([new LayoutHelper($formRenderer)]);
 *
 * $context = new LayoutContext();
 * $layout = Layouts::createLayoutFactory()->createLayoutBuilder()
 *     ->add('root', null, 'root')
 *     ->add('logo', 'root', 'logo', ['title' => 'Hello World!'])
 *     ->getLayout($context);
 *
 * echo $engine['layout']->widget($layout->getView());
 * </code>
 *
 * Also you can use 'render' method of the Layout object, but in this case
 * you need to configure the layout renderer.
 *
 * <code>
 * $formRenderer = new FormRenderer(
 *     new TemplatingRendererEngine($engine, ['OroLayoutBundle:Layout/php'])
 * );
 * $engine->addHelpers([new LayoutHelper($formRenderer)]);
 *
 * $layoutFactory = Layouts::createLayoutFactoryBuilder()
 *     ->addRenderer('php', new LayoutRenderer($formRenderer))
 *     ->getLayoutFactory();
 *
 * $context = new LayoutContext();
 * $layout = $layoutFactory->createLayoutBuilder()
 *     ->add('root', null, 'root')
 *     ->add('logo', 'root', 'logo', ['title' => 'Hello World!'])
 *     ->getLayout($context);
 *
 * echo $layout->render();
 * </code>
 */
final class Layouts
{
    /**
     * Creates a layout factory with the default configuration.
     *
     * @return LayoutFactoryInterface
     */
    public static function createLayoutFactory()
    {
        return self::createLayoutFactoryBuilder()->getLayoutFactory();
    }

    /**
     * Creates a layout factory builder with the default configuration.
     *
     * @return LayoutFactoryBuilderInterface
     */
    public static function createLayoutFactoryBuilder()
    {
        $builder = new LayoutFactoryBuilder(
            new ExpressionProcessor(
                new ExpressionLanguage(),
                new ExpressionEncoderRegistry(
                    [
                        'json' => new JsonExpressionEncoder(new ExpressionManipulator())
                    ]
                )
            )
        );

        $builder->addExtension(new CoreExtension());

        return $builder;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
