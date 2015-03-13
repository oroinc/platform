<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

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
                    'entity_label'
                ]
            )
            ->setDefaults(
                [
                    'type' => function (Options $options, $value) {
                        if ('button' === $value
                            && in_array($options['action'], ['create', 'edit', 'delete', 'cancel'], true)
                        ) {
                            $value = 'link';
                        }

                        return $value;
                    },
                    'text' => function (Options $options, $value) {
                        if (null === $value) {
                            if (!empty($options['entity_label'])) {
                                $entityLabel = $options['entity_label'];
                                if (is_string($entityLabel)) {
                                    $entityLabel = ['label' => $entityLabel];
                                }
                            }
                            switch ($options['action']) {
                                case 'cancel':
                                    $value = 'Cancel';
                                    break;
                                case 'create':
                                    $value = isset($entityLabel) ? 'oro.ui.create_entity' : 'oro.ui.create';
                                    break;
                                case 'edit':
                                    $value = isset($entityLabel) ? 'oro.ui.edit_entity' : 'oro.ui.edit';
                                    break;
                                case 'delete':
                                    $value = isset($entityLabel) ? 'oro.ui.delete_entity' : 'oro.ui.delete';
                                    break;
                                case 'save':
                                    $value = 'Save';
                                    break;
                                case 'save_and_close':
                                    $value = 'Save and Close';
                                    break;
                            }
                            if (null !== $value && isset($entityLabel)) {
                                $value = [
                                    'label'      => $value,
                                    'parameters' => ['%entityName%' => $entityLabel]
                                ];
                            }
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
        if ($options['type'] === 'link') {
            if (!empty($options['path'])) {
                $view->vars['path'] = $options['path'];
            } elseif (!empty($options['route_name'])) {
                $view->vars['route_name']       = $options['route_name'];
                $view->vars['route_parameters'] = isset($options['route_parameters'])
                    ? $options['route_parameters']
                    : [];
            }
            $view->vars['with_page_parameters'] = isset($options['with_page_parameters'])
                ? $options['with_page_parameters']
                : false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return ButtonType::NAME;
    }
}
