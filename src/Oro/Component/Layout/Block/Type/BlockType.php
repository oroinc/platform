<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BlockType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        if (isset($options['attr'])) {
            $view->vars['attr'] = $options['attr'];
        }
        if (isset($options['label'])) {
            $view->vars['label'] = $options['label'];
        }
        if (isset($options['label_attr'])) {
            $view->vars['label_attr'] = $options['label_attr'];
        }
        $view->vars['translation_domain'] = $this->getTranslationDomain($view, $options);
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
                'label_attr' => 'array',
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
