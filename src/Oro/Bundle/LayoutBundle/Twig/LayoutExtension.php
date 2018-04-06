<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Oro\Bundle\LayoutBundle\Form\TwigRendererInterface;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormView;

class LayoutExtension extends \Twig_Extension implements \Twig_Extension_InitRuntimeInterface
{
    const RENDER_BLOCK_NODE_CLASS = 'Oro\Bundle\LayoutBundle\Twig\Node\SearchAndRenderBlockNode';

    /**
     * This property is public so that it can be accessed directly from compiled
     * templates without having to call a getter, which slightly decreases performance.
     *
     * @var TwigRendererInterface
     */
    public $renderer;

    /** @var TextHelper */
    private $textHelper;

    /** @var ContainerInterface */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function initRuntime(\Twig_Environment $environment)
    {
        $this->renderer = $this->container->get('oro_layout.twig.renderer');
        $this->renderer->setEnvironment($environment);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new BlockThemeTokenParser()
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'block_widget',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'block_label',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'block_row',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'parent_block_widget',
                null,
                ['node_class' => self::RENDER_BLOCK_NODE_CLASS, 'is_safe' => ['html']]
            ),
            new \Twig_SimpleFunction(
                'layout_attr_defaults',
                [$this, 'defaultAttributes']
            ),
            new \Twig_SimpleFunction(
                'set_class_prefix_to_form',
                [$this, 'setClassPrefixToForm']
            ),
            new \Twig_SimpleFunction(
                'convert_value_to_string',
                [$this, 'convertValueToString']
            )
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // Normalizes and translates (if needed) labels in the given value.
            new \Twig_SimpleFilter('block_text', [$this, 'processText']),
            // Merge additional context to BlockView
            new \Twig_SimpleFilter('merge_context', [$this, 'mergeContext']),
        ];
    }

    /**
     * @param mixed       $value
     * @param string|null $domain
     *
     * @return mixed
     */
    public function processText($value, $domain = null)
    {
        if (null === $this->textHelper) {
            $this->textHelper = $this->container->get('oro_layout.text.helper');
        }

        return $this->textHelper->processText($value, $domain);
    }

    /**
     * @param BlockView $view
     * @param array $context
     * @return BlockView
     */
    public function mergeContext(BlockView $view, array $context)
    {
        $view->vars = array_merge($view->vars, $context);

        foreach ($view->children as $child) {
            $this->mergeContext($child, $context);
        }

        return $view;
    }

    /**
     * @param array $attr
     * @param array $defaultAttr
     * @return array
     */
    public function defaultAttributes(array $attr, array $defaultAttr)
    {
        foreach ($defaultAttr as $key => $value) {
            if (strpos($key, '~') === 0) {
                $key = substr($key, 1);
                if (array_key_exists($key, $attr)) {
                    if (is_array($value)) {
                        $attr[$key] = ArrayUtil::arrayMergeRecursiveDistinct($value, (array)$attr[$key]);
                    } else {
                        $attr[$key] .= $value;
                    }
                }
            }
            if (!array_key_exists($key, $attr)) {
                $attr[$key] = $value;
            }
        }

        return $attr;
    }

    /**
     * @param FormView $formView
     * @param $classPrefix
     */
    public function setClassPrefixToForm(FormView $formView, $classPrefix)
    {
        $formView->vars['class_prefix'] = $classPrefix;

        if (empty($formView->children) && !isset($formView->vars['prototype'])) {
            return;
        }
        foreach ($formView->children as $child) {
            $this->setClassPrefixToForm($child, $classPrefix);
        }
        if (isset($formView->vars['prototype'])) {
            $this->setClassPrefixToForm($formView->vars['prototype'], $classPrefix);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'layout';
    }

    /**
     * @param mixed $value
     * @return string
     */
    public function convertValueToString($value)
    {
        if (is_array($value)) {
            $value = stripslashes(json_encode($value));
        } elseif (is_object($value)) {
            $value = get_class($value);
        } elseif (!is_string($value)) {
            $value = var_export($value, true);
        }

        return $value;
    }
}
