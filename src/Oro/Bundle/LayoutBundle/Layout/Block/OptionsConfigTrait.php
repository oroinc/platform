<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

trait OptionsConfigTrait
{
    /**
     * Options with settings in this property allow automatically configure options and pass them to view
     * @see configureOptions()
     * @see buildView()
     *
     * @var array
     */
    protected $optionsConfig = [];

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        foreach ($this->optionsConfig as $name => $settings) {
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
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        foreach ($this->optionsConfig as $name => $settings) {
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
     * @param array $options
     */
    public function setOptionsConfig(array $options)
    {
        foreach ($options as $optionName => $optionSettings) {
            $this->validateOptionConfig($optionSettings);
        }
        $this->optionsConfig = $options;
    }

    /**
     * @param array $optionSettings
     */
    protected function validateOptionConfig(array $optionSettings = null)
    {
        if ($optionSettings === null) {
            return;
        }
        $allowedKeys = [
            'default',
            'required',
        ];
        foreach ($optionSettings as $key => $value) {
            if (!in_array($key, $allowedKeys, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Option setting "%s" not supported. Supported settings is [%s]',
                    $key,
                    implode(', ', $allowedKeys)
                ));
            }
        }
    }
}
