<?php

namespace Oro\Component\Layout\Block\Type;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockTypeInterface;
use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;

abstract class AbstractType implements BlockTypeInterface
{
    /**
     * @var array
     */
    protected $options = [];

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
        foreach ($this->options as $name => $settings) {
            if (!array_key_exists($name, $options)) {
                continue;
            }
            $define = is_array($settings) && (!empty($settings['required']) || array_key_exists('default', $settings));
            if ($define || isset($options[$name])) {
                $view->vars[$name] = $options[$name];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function finishView(BlockView $view, BlockInterface $block, array $options)
    {
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        foreach ($this->options as $name => $settings) {
            $resolver->setDefined($name);
            if (!is_array($settings)) {
                continue;
            }
            if (isset($settings['required']) && $settings['required']) {
                $resolver->setRequired($name);
            }
            if (array_key_exists('default', $settings)) {
                $resolver->setDefault($name, $settings['default']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return BaseType::NAME;
    }

    /**
     * @return array
     */
    protected function getOptionSettings()
    {
        return [
            'default',
            'required',
        ];
    }
}
