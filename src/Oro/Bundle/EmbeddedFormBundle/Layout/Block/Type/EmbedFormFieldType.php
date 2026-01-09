<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;
use Symfony\Component\Form\FormView;

/**
 * Layout block type for rendering individual embedded form fields.
 *
 * This block type is responsible for rendering a single form field within an embedded form
 * layout. It extracts the specified field from the form using a field path and renders it
 * as a layout block. It also handles visibility logic, preventing `form_rest()` from rendering
 * fields that are marked as invisible in the layout.
 */
class EmbedFormFieldType extends AbstractFormType
{
    public const NAME = 'embed_form_field';

    public const SHORT_NAME = 'field';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(['form_name', 'field_path']);
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $options);
        $view->vars['form'] = $formAccessor->getView($options['field_path']);

        BlockUtils::setViewVarsFromOptions($view, $options, ['field_path']);
    }

    #[\Override]
    public function finishView(BlockView $view, BlockInterface $block)
    {
        // prevent the form field rendering by form_rest() method,
        // if the corresponding layout block is invisible
        if ($view->vars['visible'] === false) {
            /** @var FormView $formView */
            $formView = $view->vars['form'];
            $formView->setRendered();
        }
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
