<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Type;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class RequireJsType extends AbstractType
{
    const NAME = 'require_js';

    /** @var bool */
    protected $isDebug;

    /**
     * @param bool $isDebug
     */
    public function __construct($isDebug = false)
    {
        $this->isDebug = $isDebug;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['compressed']);
        $resolver->setNormalizers(
            [
                'compressed' => function (Options $options, $compressed) {
                    if ($compressed === null) {
                        $compressed = !$this->isDebug;
                    }

                    return $compressed;
                }
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['compressed'] = $options['compressed'];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
