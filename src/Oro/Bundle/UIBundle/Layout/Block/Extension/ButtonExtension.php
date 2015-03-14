<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\Util\BlockUtils;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\ButtonType;

/**
 * Adds support of the "link" button type.
 * Adds support of the following actions: 'create', 'edit', 'delete', 'cancel', 'save', 'save_and_close'.
 * Also the "link" button can be used with the "with_page_parameters" option, that
 * adds current page query string parameters to the link url.
 */
class ButtonExtension extends AbstractBlockTypeExtension
{
    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver
            ->setOptional(
                [
                    'path',
                    'route_name',
                    'route_parameters',
                    'with_page_parameters',
                    'entity_label',
                    'entity_id',
                    'redirect_path',
                    'redirect_route_name',
                    'redirect_route_parameters',
                    'confirm_message',
                    'success_message'
                ]
            )
            ->setNormalizers(
                [
                    'type'            => function (Options $options, $value) {
                        if ('button' === $value
                            && in_array($options['action'], ['create', 'edit', 'delete', 'cancel'], true)
                        ) {
                            $value = 'link';
                        }

                        return $value;
                    },
                    'text'            => function (Options $options, $value) {
                        if (null === $value) {
                            switch ($options['action']) {
                                case 'cancel':
                                    $value = 'Cancel';
                                    break;
                                case 'create':
                                    $entityLabel = $this->getEntityLabel($options);
                                    $value       = $entityLabel ? 'oro.ui.create_entity' : 'oro.ui.create';
                                    break;
                                case 'edit':
                                    $value = 'oro.ui.edit';
                                    break;
                                case 'delete':
                                    $value = 'oro.ui.delete';
                                    break;
                                case 'save':
                                    $value = 'Save';
                                    break;
                                case 'save_and_close':
                                    $value = 'Save and Close';
                                    break;
                            }
                            if (null !== $value && isset($entityLabel)) {
                                $value = BlockUtils::normalizeTransValue(
                                    $value,
                                    ['%entityName%' => $entityLabel]
                                );
                            }
                        }

                        return $value;
                    },
                    'attr'            => function (Options $options, $value) {
                        if (null === $value) {
                            $value = [];
                        }
                        if (!isset($value['title'])) {
                            switch ($options['action']) {
                                case 'edit':
                                    $entityLabel = $this->getEntityLabel($options);
                                    $title       = $entityLabel ? 'oro.ui.edit_entity' : 'oro.ui.edit';
                                    break;
                                case 'delete':
                                    $entityLabel = $this->getEntityLabel($options);
                                    $title       = $entityLabel ? 'oro.ui.delete_entity' : 'oro.ui.delete';
                                    break;
                            }
                            if (!empty($title)) {
                                if (isset($entityLabel)) {
                                    $title = BlockUtils::normalizeTransValue(
                                        $title,
                                        ['%entityName%' => $entityLabel]
                                    );
                                }
                                $value['title'] = $title;
                            }
                        }

                        return $value;
                    },
                    'confirm_message' => function (Options $options, $value) {
                        if (null === $value && $options['action'] === 'delete') {
                            $value = BlockUtils::normalizeTransValue(
                                'oro.ui.delete_confirm',
                                ['%entity_label%' => $this->getEntityLabel($options) ?: ['label' => 'oro.ui.item']]
                            );
                        }

                        return $value;
                    },
                    'success_message' => function (Options $options, $value) {
                        if (null === $value && $options['action'] === 'delete') {
                            $value = BlockUtils::normalizeTransValue(
                                'oro.ui.delete_message',
                                ['%entity_label%' => $this->getEntityLabel($options) ?: ['label' => 'oro.ui.item']]
                            );
                        }

                        return $value;
                    }
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        BlockUtils::processUrl($view, $options);
        BlockUtils::processUrl($view, $options, false, 'redirect');

        if (isset($view->vars['path'])
            || isset($view->vars['route_name'])
            || isset($view->vars['redirect_path'])
            || isset($view->vars['redirect_route_name'])
        ) {
            $view->vars['with_page_parameters'] = isset($options['with_page_parameters'])
                ? $options['with_page_parameters']
                : false;
        }

        if (!empty($options['entity_label'])) {
            $view->vars['entity_label'] = $options['entity_label'];
        }
        if (!empty($options['entity_id'])) {
            $view->vars['entity_id'] = $options['entity_id'];
        }
        if (!empty($options['confirm_message'])) {
            $view->vars['confirm_message'] = $options['confirm_message'];
        }
        if (!empty($options['success_message'])) {
            $view->vars['success_message'] = $options['success_message'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
        BlockUtils::registerPlugin($view, $options['action'] . '_' . $block->getTypeName());
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ButtonType::NAME;
    }

    /**
     * @param Options $options
     *
     * @return array|null
     */
    protected function getEntityLabel(Options $options)
    {
        return !empty($options['entity_label'])
            ? BlockUtils::normalizeTransValue($options['entity_label'])
            : null;
    }
}
