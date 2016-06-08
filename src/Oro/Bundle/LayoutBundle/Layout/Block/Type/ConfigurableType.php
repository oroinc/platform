<?php

namespace Oro\Bundle\LayoutBundle\Layout\Block\Type;

use Oro\Component\Layout\Block\Type\AbstractType;

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
     * @param array $options
     */
    public function setOptions(array $options)
    {
        foreach ($options as $optionName => $optionSettings) {
            $this->validateOptionSettings($optionSettings);
        }
        $this->options = $options;
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
