<?php

namespace Oro\Bundle\ActionBundle\Model;

class ActionData extends AbstractStorage implements EntityAwareInterface
{
    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->offsetGet('data');
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl()
    {
        return $this->offsetGet('redirectUrl');
    }

    /**
     * @return array|null
     */
    public function getRefreshGrid()
    {
        return $this->offsetGet('refreshGrid');
    }

    /**
     * @return array
     */
    protected function getMergeKeys()
    {
        return ['redirectUrl', 'refreshGrid'];
    }

    /**
     * @param ActionData $data
     */
    public function merge(ActionData $data = null)
    {
        if (!$data) {
            return;
        }

        foreach ($this->getMergeKeys() as $key) {
            $value = $data->offsetGet($key);

            if (is_array($value)) {
                $value = array_merge($this->offsetGet($key), $value);
            }

            if (null !== $value) {
                $this->offsetSet($key, $value);
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    /**
     * @param string $name
     */
    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

    /**
     * @param array $names
     * @return array
     */
    public function getValues(array $names = [])
    {
        if (!$names) {
            return $this->data;
        }

        $result = [];

        foreach ($names as $name) {
            $result[$name] = $this->offsetGet($name);
        }

        return $result;
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
