<?php

namespace Oro\Component\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\BlockBuilderInterface;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\BlockTypeInterface;

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
        $optionsNames = array_keys($this->options);
        foreach ($optionsNames as $name) {
            if (array_key_exists($name, $options)) {
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
     * @param OptionsResolver|OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        foreach ($this->options as $name => $settings) {
            $resolver->setDefined($name);
            if (!is_array($settings)) {
                continue;
            }
            if (array_key_exists('required', $settings) && $settings['required']) {
                $resolver->setRequired($name);
            }
            if (array_key_exists('default', $settings)) {
                $resolver->setDefault($name, $settings['default']);
            }
            if (array_key_exists('normalizers', $settings)) {
                $resolver->setNormalizer($name, $settings['normalizers']);
            }
            if (array_key_exists('allowed_values', $settings)) {
                $resolver->setAllowedValues($name, $settings['allowed_values']);
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
            'normalizers',
            'allowed_values',
        ];
    }
}
