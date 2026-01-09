<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\Options;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

/**
 * Provides automatic options configuration and view variable assignment for block types.
 *
 * This trait enables block types and extensions to declaratively define options
 * through an `optionsConfig` array, automatically handling option resolution with
 * support for required options and default values. It also automatically passes
 * configured options to the block view, reducing boilerplate code in block type
 * implementations.
 */
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

    public function buildView(BlockView $view, BlockInterface $block, Options $options)
    {
        foreach ($this->optionsConfig as $name => $settings) {
            if (!$options->offsetExists($name)) {
                continue;
            }
            $define = is_array($settings) && (!empty($settings['required']) || array_key_exists('default', $settings));
            $value = $options->get($name, false);
            if ($define || !is_null($value)) {
                $view->vars[$name] = $value;
            }
        }
    }

    public function setOptionsConfig(array $options)
    {
        foreach ($options as $optionName => $optionSettings) {
            $this->validateOptionConfig($optionSettings);
        }
        $this->optionsConfig = $options;
    }

    protected function validateOptionConfig(?array $optionSettings = null)
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
