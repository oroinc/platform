<?php

namespace Oro\Bundle\ApiBundle\Metadata;

use Oro\Component\ChainProcessor\ToArrayInterface;

/**
 * The metadata for an entity property that provides some meta information about this entity.
 */
class MetaPropertyMetadata extends PropertyMetadata implements ToArrayInterface
{
    /** @var string */
    private $resultName;

    /**
     * Gets the name by which the meta property should be returned in the response.
     *
     * @return string
     */
    public function getResultName()
    {
        if (null === $this->resultName) {
            return $this->getName();
        }

        return $this->resultName;
    }

    /**
     * Sets the name by which the meta property should be returned in the response.
     *
     * @param string $resultName
     */
    public function setResultName($resultName)
    {
        $this->resultName = $resultName;
    }
}
