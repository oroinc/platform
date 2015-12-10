<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ParameterBag;

abstract class PropertyMetadata extends ParameterBag
{
    /** the name of a property */
    const NAME = 'name';

    /** the data-type of a property */
    const DATA_TYPE = 'dataType';

    /**
     * Gets the name of a property.
     *
     * @return string
     */
    public function getName()
    {
        return $this->get(self::NAME);
    }

    /**
     * Sets the name of a property.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->set(self::NAME, $name);
    }

    /**
     * Gets the data-type of a property.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * Sets the data-type of a property.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->set(self::DATA_TYPE, $dataType);
    }
}
