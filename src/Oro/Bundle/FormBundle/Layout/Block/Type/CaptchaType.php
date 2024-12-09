<?php

namespace Oro\Bundle\FormBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Implements CAPTCHA Block Form Type for Layout
 */
class CaptchaType extends AbstractType
{
    public const NAME = 'captcha';

    #[\Override]
    public function getName()
    {
        return self::NAME;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('name', 'captcha');
    }

    #[\Override]
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        $view->vars['name'] = $options->get('name');
    }
}
