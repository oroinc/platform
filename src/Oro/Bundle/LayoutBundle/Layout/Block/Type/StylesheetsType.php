<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\OptionValueBag;
use Oro\Component\Layout\ArrayOptionValueBuilder;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class StylesheetsType extends AbstractType
{
    const NAME = 'stylesheets';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            [
                'inputs'
            ]
        );
        $resolver->setDefaults(
            [
                'filters' => [
                    'cssrewrite',
                    'lessphp',
                    'cssmin',
                ],
                'output' => null,
            ]
        );

        $resolver->setAllowedTypes('inputs', ['array', 'Oro\Component\Layout\OptionValueBag']);
        $resolver->setAllowedTypes('filters', ['array', 'Oro\Component\Layout\OptionValueBag']);
        $resolver->setAllowedTypes('output', ['null', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        $theme = $block->getContext()->get('theme');
        
        $inputs = $block->getOptions()['inputs'];
        if ($inputs instanceof OptionValueBag) {
            $inputs = $inputs->buildValue(new ArrayOptionValueBuilder());
        }
        $view->vars['inputs'] = $inputs;

        $filters = $block->getOptions()['filters'];
        if ($filters instanceof OptionValueBag) {
            $filters = $filters->buildValue(new ArrayOptionValueBuilder());
        }
        $view->vars['filters'] = $filters;

        $view->vars['output'] = $block->getOptions()['output'];
        if (!$view->vars['output']) {
            $view->vars['output'] = 'css/layout/'.$theme.'/'.$view->vars['cache_key'].'.css';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
