<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class BaseType extends AbstractType
{
    const NAME = 'block';

    public function __construct()
    {
        $this->options = [
            'vars' => null,
            'attr' => null,
            'label' => null,
            'label_attr' => null,
            'translation_domain' => null,
            'class_prefix' => null,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        // merge the passed variables with the existing ones
        if (!empty($options['vars'])) {
            $view->vars = array_replace($view->vars, $options['vars']);
        }

        // add the view to itself vars to allow get it using 'block' variable in a rendered, for example TWIG
        $view->vars['block'] = $view;

        $view->vars['class_prefix'] = null;
        if (isset($options['class_prefix'])) {
            $view->vars['class_prefix'] = $options['class_prefix'];
        } elseif ($view->parent) {
            $view->vars['class_prefix'] = $view->parent->vars['class_prefix'];
        }

        // replace attributes if specified ('attr' variable always exists in a view because it is added by FormView)
        if (isset($options['attr'])) {
            $view->vars['attr'] = $options['attr'];
        }

        // add label text and attributes if specified
        if (isset($options['label'])) {
            $view->vars['label'] = $options['label'];
            $view->vars['label_attr'] = [];
            if (isset($options['label_attr'])) {
                $view->vars['label_attr'] = $options['label_attr'];
            }
        }

        // add the translation domain
        $view->vars['translation_domain'] = $this->getTranslationDomain($view, $options);

        // add core variables to the block view, like id, block type and variables required for rendering engine
        $id   = $block->getId();
        $name = $block->getTypeName();

        // the block prefix must contain only letters, numbers and underscores (_)
        // due to limitations of block names in TWIG
        $uniqueBlockPrefix = '_' . preg_replace('/[^a-z0-9_]+/i', '_', $id);
        $blockPrefixes     = $block->getTypeHelper()->getTypeNames($name);
        $blockPrefixes[]   = $uniqueBlockPrefix;

        $view->vars['id']                  = $id;
        $view->vars['block_type']          = $name;
        $view->vars['unique_block_prefix'] = $uniqueBlockPrefix;
        $view->vars['block_prefixes']      = $blockPrefixes;
        $view->vars['cache_key']           = sprintf('_%s_%s', $id, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        if (isset($view->vars['attr']['id']) && !isset($view->vars['label_attr']['for'])) {
            $view->vars['label_attr']['for'] = $view->vars['attr']['id'];
        }

        $view->vars['blocks'] = $view->blocks;
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
        return self::NAME;
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
        if (!$translationDomain && $view->parent) {
            $translationDomain = $view->parent->vars['translation_domain'];
        }
        if (!$translationDomain) {
            $translationDomain = 'messages';
        }

        return $translationDomain;
    }
}
