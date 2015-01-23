<?php

namespace Oro\Component\Layout\Block;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\BlockView;

class BaseType implements BlockTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function buildBlock(BlockBuilderInterface $builder, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['translation_domain'] = $this->getTranslationDomain($view, $options);
        if (isset($options['attr'])) {
            $view->vars['attr'] = $options['attr'];
        }
        if (isset($options['label'])) {
            $view->vars['label'] = $options['label'];
        }
        if (isset($options['label_attr'])) {
            $view->vars['label_attr'] = $options['label_attr'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['attr', 'label', 'label_attr', 'translation_domain']);
        $resolver->setAllowedTypes(
            [
                'attr'       => 'array',
                'label_attr' => 'label_attr',
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'block';
    }

    /**
     * @param BlockView $view
     * @param array     $options
     *
     * @return string
     */
    protected function getTranslationDomain(BlockView $view, array $options)
    {
        $translationDomain = isset($options['translation_domain'])
            ? $options['translation_domain']
            : null;
        if ($view->parent) {
            if (!$translationDomain) {
                $translationDomain = $view->parent->vars['translation_domain'];
            }
        }
        if (!$translationDomain) {
            $translationDomain = 'messages';
        }

        return $translationDomain;
    }
}
