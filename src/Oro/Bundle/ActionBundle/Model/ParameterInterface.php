<?php

namespace Oro\Bundle\ActionBundle\Model;

interface ParameterInterface
{
    const INTERNAL_TYPE_ATTRIBUTE = 'attribute';
    const INTERNAL_TYPE_VARIABLE = 'variable';

    /**
     * Set attribute type
     *
     * @param string $type
     * @return ParameterInterface
     */
    public function setType($type);

    /**
     * Get attribute type
     *
     * @return string
     */
    public function getType();

    /**
     * Set attribute label.
     *
     * @param string $label
     * @return ParameterInterface
     */
    public function setLabel($label);

    /**
     * Get attribute label.
     *
     * @return string
     */
    public function getLabel();

    /**
     * Set attribute name.
     *
     * @param string $name
     * @return ParameterInterface
     */
    public function setName($name);

    /**
     * Get attribute name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set options.
     *
     * @param array $options
     * @return ParameterInterface
     */
    public function setOptions(array $options);

    /**
     * Get options.
     *
     * @return array
     */
    public function getOptions();

    /**
     * Set option by key.
     *
     * @param string $key
     * @param mixed $value
     * @return ParameterInterface
     */
    public function setOption($key, $value);

    /**
     * Get option by key.
     *
     * @param string $key
     * @return null|mixed
     */
    public function getOption($key);

    /**
     * Check for option availability by key.
     *
     * @param string $key
     * @return bool
     */
    public function hasOption($key);

    /**
     * @return string
     */
    public function getInternalType();
}
