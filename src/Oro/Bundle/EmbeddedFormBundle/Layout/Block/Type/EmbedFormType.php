<?php

namespace Oro\Bundle\EmbeddedFormBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\Block\Type\AbstractType;

class EmbedFormType extends AbstractType
{
    const NAME = 'embed_form_legacy_form';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['form' => null, 'form_layout' => null]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['form']        = $options['form'];
        $view->vars['form_layout'] = $options['form_layout'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
