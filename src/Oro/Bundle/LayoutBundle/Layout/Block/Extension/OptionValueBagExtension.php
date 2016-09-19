<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Extension;

use Symfony\Component\Finder\Expression\Expression;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\Action;
use Oro\Component\Layout\ArrayOptionValueBuilder;
use Oro\Component\Layout\Block\Type\BaseType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataAccessorInterface;
use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\OptionValueBuilderInterface;
use Oro\Component\Layout\StringOptionValueBuilder;
use Oro\Component\Layout\Util\BlockUtils;

/**
 * Automatically converts OptionValueBag to an appropriate data representation
 */
class OptionValueBagExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['resolve_value_bags' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function normalizeOptions(Options $options, ContextInterface $context, DataAccessorInterface $data)
    {
        if ($options['resolve_value_bags']) {
            $this->resolveOptions($options);
        }
    }

    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['resolve_value_bags']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $exprEvaluate = $block->getContext()->getOr('expressions_evaluate');
        if ($view->vars['resolve_value_bags'] && $exprEvaluate) {
            array_walk_recursive(
                $view->vars,
                function (&$var) {
                    if ($var instanceof OptionValueBag) {
                        $var = $var->buildValue($this->getOptionsBuilder($var));
                    }
                }
            );
        }
    }

    /**
     * @param  $options
     * @return Options
     */
    protected function resolveOptions(Options $options)
    {
        foreach ($options as $key => $value) {
            if ($value instanceof Expression) {
                continue;
            }
            if ($value instanceof Options) {
                $options[$key] = $this->resolveOptions($value);
            } elseif ($value instanceof OptionValueBag) {
                $options[$key] = $value->buildValue($this->getOptionsBuilder($value));
            }
        }

        return $options;
    }

    /**
     * @param OptionValueBag $valueBag
     * @return OptionValueBuilderInterface
     */
    protected function getOptionsBuilder(OptionValueBag $valueBag)
    {
        $isArray = false;

        // guess builder type based on arguments
        $actions = $valueBag->all();
        if ($actions) {
            /** @var Action $action */
            $action = reset($actions);
            $arguments = $action->getArguments();
            if ($arguments) {
                $argument = reset($arguments);
                if (is_array($argument)) {
                    $isArray = true;
                }
            }
        }

        if ($isArray) {
            return new ArrayOptionValueBuilder();
        }

        return new StringOptionValueBuilder();
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return BaseType::NAME;
    }
}
