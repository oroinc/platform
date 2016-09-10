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
            $this->resolveValueBags($options);
        }
    }

    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['resolve_value_bags'] = $options->get('resolve_value_bags', false);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $exprEvaluate = $block->getContext()->getOr('expressions_evaluate');
        if ($view->vars['resolve_value_bags'] && $exprEvaluate) {
            $this->resolveValueBags($view->vars);
        }
    }

    /**
     * @param Options $options
     * @return Options
     */
    protected function resolveValueBags($options)
    {
        foreach ($options as $key => $value) {
            if ($value instanceof Expression) {
                continue;
            }
            if ($value instanceof Options) {
                $options[$key] = $this->resolveValueBags($value);
            } elseif ($value instanceof OptionValueBag) {
                $options[$key] = $value->buildValue($this->getOptionsBuilder($value, $options));
            }
        }

        return $options;
    }

    /**
     * @param OptionValueBag $valueBag
     * @param Options $options
     * @return OptionValueBuilderInterface
     */
    protected function getOptionsBuilder(OptionValueBag $valueBag, Options $options)
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
