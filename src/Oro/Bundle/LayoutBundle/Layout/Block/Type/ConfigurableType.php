<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\OptionsResolver\OptionsResolver;
use Oro\Component\Layout\Block\Type\AbstractType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ConfigurableType extends AbstractType
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $parent;

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
            $this->validateOptionSettings($optionSettings);
        }
        $this->optionsConfig = $options;
    }
    
    /**
     * @return mixed
     */
    public function getName()
    {
        if ($this->name === null) {
            throw new \LogicException('Block type "name" does not configured');
        }
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        if (!is_string($name)) {
            throw new \InvalidArgumentException('Block type "name" should be string');
        }
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent ?: parent::getParent();
    }

    /**
     * @param string $parent
     * @return mixed
     */
    public function setParent($parent)
    {
        if (!is_string($parent)) {
            throw new \InvalidArgumentException('Block type "parent" should be string');
        }
        $this->parent = $parent;
        return $this;
    }

    /**
     * @param array $optionSettings
     */
    protected function validateOptionSettings(array $optionSettings = null)
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
                    'Option setting "%s" not supported. Supported settings [%s]',
                    $key,
                    implode(', ', $allowedKeys)
                ));
            }
        }
    }
}
