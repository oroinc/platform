<?php

namespace Oro\Bundle\ApiBundle\Processor\NormalizeValue;

use Oro\Bundle\ApiBundle\Processor\ApiContext;

class NormalizeValueContext extends ApiContext
{
    /** API version */
    const DATA_TYPE = 'dataType';

    /** API version */
    const REQUIREMENT = 'requirement';

    /**
     * {@inheritdoc}
     */
    public function getDataType()
    {
        return $this->get(self::DATA_TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setDataType($dataType)
    {
        $this->set(self::DATA_TYPE, $dataType);
    }

    /**
     * {@inheritdoc}
     */
    public function hasRequirement()
    {
        return $this->has(self::REQUIREMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function getRequirement()
    {
        return $this->get(self::REQUIREMENT);
    }

    /**
     * {@inheritdoc}
     */
    public function setRequirement($data)
    {
        $this->set(self::REQUIREMENT, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function removeRequirement()
    {
        $this->remove(self::REQUIREMENT);
    }
}
