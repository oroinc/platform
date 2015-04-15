<?php

namespace Oro\Component\Layout;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Oro\Component\PropertyAccess\PropertyPath;

class BlockOptionsManipulator implements BlockOptionsManipulatorInterface
{
    /** @var RawLayout */
    protected $rawLayout;

    /** @var PropertyAccessor */
    protected $propertyAccessor;

    /** @var PropertyPath[] */
    private $cache = [];

    public function __construct()
    {
        $this->propertyAccessor = new PropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function setRawLayout(RawLayout $rawLayout)
    {
        $this->rawLayout = $rawLayout;
    }

    /**
     * {@inheritdoc}
     */
    public function setOption($id, $optionName, $optionValue)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $this->propertyAccessor->setValue($options, $this->getPropertyPath($optionName), $optionValue);
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function appendOption($id, $optionName, $optionValue)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function subtractOption($id, $optionName, $optionValue)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function removeOption($id, $optionName)
    {
        $options = $this->rawLayout->getProperty($id, RawLayout::OPTIONS);
        $this->propertyAccessor->removeValue($options, $this->getPropertyPath($optionName));
        $this->rawLayout->setProperty($id, RawLayout::OPTIONS, $options);
    }

    /**
     * @param string $optionName The option name or path
     *
     * @return PropertyPath
     */
    protected function getPropertyPath($optionName)
    {
        if (isset($this->cache[$optionName])) {
            $propertyPath = $this->cache[$optionName];
        } else {
            $propertyPath             = new PropertyPath($optionName);
            $this->cache[$optionName] = $propertyPath;
        }

        return $propertyPath;
    }
}
