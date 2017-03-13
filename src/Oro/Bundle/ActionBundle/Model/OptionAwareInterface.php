<?php

namespace Oro\Bundle\ActionBundle\Model;

interface OptionAwareInterface
{
    /**
     * Set options.
     *
     * @param array $options
     *
     * @return OptionAwareInterface
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
     *
     * @return OptionAwareInterface
     */
    public function setOption($key, $value);

    /**
     * Get option by key.
     *
     * @param string $key
     *
     * @return null|mixed
     */
    public function getOption($key);

    /**
     * Check for option availability by key.
     *
     * @param string $key
     *
     * @return bool
     */
    public function hasOption($key);
}
