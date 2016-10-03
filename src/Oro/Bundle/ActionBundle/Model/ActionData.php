<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Component\Action\Model\AbstractStorage;

class ActionData extends AbstractStorage implements EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->get('data');
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl()
    {
        $result = $this->get('result');

        return is_array($result) && isset($result['redirectUrl']) ? $result['redirectUrl'] : null;
    }

    /**
     * @return array|null
     */
    public function getRefreshGrid()
    {
        return $this->get('refreshGrid');
    }

    /**
     * @return array
     */
    public function getScalarValues()
    {
        $scalars = [];

        foreach ($this->data as $key => $value) {
            if (is_scalar($value)) {
                $scalars[$key] = $value;
            }
        }

        return $scalars;
    }
}
