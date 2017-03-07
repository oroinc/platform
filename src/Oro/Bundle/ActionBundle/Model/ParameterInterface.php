<?php

namespace Oro\Bundle\ActionBundle\Model;

interface ParameterInterface extends OptionAwareInterface
{
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
     * @return string
     */
    public function getInternalType();
}
