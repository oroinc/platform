<?php

namespace Oro\Bundle\ActionBundle\Model;

use Oro\Component\Action\Model\AbstractStorage;

class ActionData extends AbstractStorage implements EntityAwareInterface
{
    const OPERATION_TOKEN = '_operation_token';

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
        return $this->get('redirectUrl');
    }

    /**
     * @return array|null
     */
    public function getRefreshGrid()
    {
        return $this->get('refreshGrid');
    }

    /**
     * @return string
     */
    public function getOperationToken()
    {
        return $this->get(self::OPERATION_TOKEN);
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
