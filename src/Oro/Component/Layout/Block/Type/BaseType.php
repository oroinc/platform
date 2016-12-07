<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

class BaseType extends AbstractType
{
    const NAME = 'block';

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setDefaults([
            'visible' => true,
        ]);

        $optionsResolver->setDefined([
            'vars',
            'attr',
            'label',
            'label_attr',
            'translation_domain',
            'class_prefix',
            'additional_block_prefixes'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        BlockUtils::setViewVarsFromOptions(
            $view,
            $options,
            ['visible', 'translation_domain', 'additional_block_prefixes', 'class_prefix']
        );

        // merge the passed variables with the existing ones
        if (!empty($options['vars'])) {
            foreach ($options['vars'] as $name => $value) {
                $view->vars[$name] = $value;
            }
        }

        // replace attributes if specified ('attr' variable always exists in a view because it is added by FormView)
        if (isset($options['attr'])) {
            $view->vars['attr'] = $options['attr'];
        }

        // add label text and attributes if specified
        if (isset($options['label'])) {
            $view->vars['label'] = $options->get('label', false);
            $view->vars['label_attr'] = [];
            if (isset($options['label_attr'])) {
                $view->vars['label_attr'] = $options->get('label_attr', false);
            }
        }

        // add core variables to the block view, like id, block type and variables required for rendering engine
        $view->vars['id']                   = $block->getId();
        $view->vars['block_type_widget_id'] = $block->getTypeName() . '_widget';
        $view->vars['block_type']           = $block->getTypeName();
        $view->vars['unique_block_prefix']  = '_' . preg_replace('/[^a-z0-9_]+/i', '_', $block->getId());
        $view->vars['cache_key']            = sprintf('_%s_%s', $block->getId(), $block->getTypeName());
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block)
    {
        if (empty($view->vars['class_prefix']) && $view->parent) {
            $view->vars['class_prefix'] = $view->parent->vars['class_prefix'];
        }

        // add the view to itself vars to allow get it using 'block' variable in a rendered, for example TWIG
        $view->vars['block'] = $view;

        // add the translation domain
        $view->vars['translation_domain'] = $this->getTranslationDomain($view);

        // the block prefix must contain only letters, numbers and underscores (_)
        // due to limitations of block names in TWIG
        $blockPrefixes = $block->getTypeHelper()->getTypeNames($block->getTypeName());

        if (!empty($view->vars['additional_block_prefixes'])) {
            $blockPrefixes = array_merge($blockPrefixes, $view->vars['additional_block_prefixes']);
        }
        unset($view->vars['additional_block_prefixes']);

        $blockPrefixes[] = $view->vars['unique_block_prefix'];

        $view->vars['block_prefixes'] = $blockPrefixes;

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
     *
     * @return string
     */
    protected function getTranslationDomain(BlockView $view)
    {
        $translationDomain = $view->vars['translation_domain'];
        if (!$translationDomain && $view->parent) {
            $translationDomain = $view->parent->vars['translation_domain'];
        }
        if (!$translationDomain) {
            $translationDomain = 'messages';
        }

        return $translationDomain;
    }
}
