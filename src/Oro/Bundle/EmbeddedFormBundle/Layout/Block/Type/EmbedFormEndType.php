<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

/**
 * Embed form end block type.
 */
class EmbedFormEndType extends AbstractFormType
{
    const NAME = 'embed_form_end';

    const SHORT_NAME = 'end';

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(['render_rest' => false]);
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions($view, $options, ['render_rest']);
        parent::buildView($view, $block, $options);
    }

    #[\Override]
    public function finishView(BlockView $view, BlockInterface $block)
    {
        $formAccessor = $this->getFormAccessor($block->getContext(), $view->vars);

        $view->vars['form'] = $formAccessor->getView();
    }

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }
}
