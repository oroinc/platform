<?php

namespace Oro\Bundle\ConfigBundle\Config\Tree;

class FieldNodeDefinition extends AbstractNodeDefinition
{
    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return isset($this->definition['property_path']) ? $this->definition['property_path'] : $this->getName();
    }

    /**
     * Return field type
     *
     * @return string
     */
    public function getType()
    {
        return $this->definition['type'];
    }

    /**
     * Return acl resource name if defined
     *
     * @return bool|string
     */
    public function getAclResource()
    {
        if (!empty($this->definition['acl_resource'])) {
            return $this->definition['acl_resource'];
        }

        return false;
    }

    /**
     * Get field options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->definition['options'];
    }

    /**
     * Set field options
     *
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->definition['options'] = $options;

        return $this;
    }

    /**
     * Replace field option by name
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return $this
     */
    public function replaceOption($name, $value)
    {
        $this->definition['options'][$name] = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function needsPageReload()
    {
        return $this->definition['page_reload'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareDefinition(array $definition)
    {
        if (!isset($definition['options'])) {
            $definition['options'] = [];
        }

        return parent::prepareDefinition($definition);
    }
}
