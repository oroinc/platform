<?php

namespace Oro\Bundle\LayoutBundle\Twig;

use Doctrine\Inflector\Inflector;
use Oro\Bundle\LayoutBundle\Twig\Node\SearchAndRenderBlockNode;
use Oro\Bundle\LayoutBundle\Twig\TokenParser\BlockThemeTokenParser;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Templating\TextHelper;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Container\ContainerInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Provides Twig functions to work with layout blocks:
 *   - block_widget
 *   - block_label
 *   - block_row
 *   - parent_block_widget
 *   - layout_attr_defaults
 *   - set_class_prefix_to_form
 *   - convert_value_to_string
 *   - highlight_string
 *   - clone_form_view_with_unique_id
 *
 * Provides Twig filters for string manipulations:
 *   - block_text - normalizes and translates (if needed) labels in the given value.
 *   - merge_context - merges additional context to BlockView.
 *   - pluralize
 *
 * Provides Twig tests for string content identification:
 *   - expression
 *   - string
 *
 * Provides a Twig tag for setting block theme:
 *   - block_theme
 */
class LayoutExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private Inflector $inflector;
    private ?TextHelper $textHelper = null;

    public function __construct(ContainerInterface $container, Inflector $inflector)
    {
        $this->container = $container;
        $this->inflector = $inflector;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new BlockThemeTokenParser(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'block_widget',
                null,
                ['node_class' => SearchAndRenderBlockNode::class, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'block_label',
                null,
                ['node_class' => SearchAndRenderBlockNode::class, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'block_row',
                null,
                ['node_class' => SearchAndRenderBlockNode::class, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'parent_block_widget',
                null,
                ['node_class' => SearchAndRenderBlockNode::class, 'is_safe' => ['html']]
            ),
            new TwigFunction(
                'layout_attr_defaults',
                [$this, 'defaultAttributes']
            ),
            new TwigFunction(
                'set_class_prefix_to_form',
                [$this, 'setClassPrefixToForm']
            ),
            new TwigFunction(
                'convert_value_to_string',
                [$this, 'convertValueToString']
            ),
            new TwigFunction(
                'highlight_string',
                [$this, 'highlightString'],
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'clone_form_view_with_unique_id',
                [$this, 'cloneFormViewWithUniqueId']
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // Normalizes and translates (if needed) labels in the given value.
            new TwigFilter('block_text', [$this, 'processText']),
            // Merge additional context to BlockView
            new TwigFilter('merge_context', [$this, 'mergeContext']),
            new TwigFilter('pluralize', [$this->inflector, 'pluralize']),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('expression', [$this, 'isExpression']),
            new TwigTest('string', [$this, 'isString']),
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
     * @param array     $context
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
            if (str_starts_with($key, '~')) {
                $key = substr($key, 1);
                if (\array_key_exists($key, $attr)) {
                    if (\is_array($value)) {
                        $attr[$key] = ArrayUtil::arrayMergeRecursiveDistinct($value, (array)$attr[$key]);
                    } else {
                        $attr[$key] .= $value;
                    }
                }
            }
            if (!\array_key_exists($key, $attr)) {
                $attr[$key] = $value;
            }
        }

        return $attr;
    }

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
     * @param mixed $value
     * @return string
     */
    public function convertValueToString($value)
    {
        if (\is_array($value)) {
            $value = stripslashes(json_encode($value));
        } elseif (\is_object($value)) {
            $value = \get_class($value);
        } elseif (!\is_string($value)) {
            $value = var_export($value, true);
        }

        return $value;
    }

    /**
     * @param $value
     * @return string
     */
    public function highlightString($value)
    {
        $highlightString = @highlight_string('<?php '.$value, true);
        $highlightString = str_replace('&lt;?php&nbsp;', '', $highlightString);

        return $highlightString;
    }

    public function cloneFormViewWithUniqueId(FormView $form, string $uniqueId, FormView $parent = null): FormView
    {
        $newForm = new FormView($parent);
        $newForm->vars = $form->vars;
        $newForm->vars['id'] = sprintf('%s-%s', $form->vars['id'], $uniqueId);
        $newForm->vars['form'] = $newForm;

        foreach ($form->children as $name => $child) {
            $newForm->children[$name] = $this->cloneFormViewWithUniqueId($child, $uniqueId, $newForm);
        }

        return $newForm;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isExpression($value)
    {
        return $value instanceof Expression;
    }

    /**
     * @param $value
     * @return bool
     */
    public function isString($value)
    {
        return \is_string($value);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_layout.text.helper' => TextHelper::class,
        ];
    }
}
