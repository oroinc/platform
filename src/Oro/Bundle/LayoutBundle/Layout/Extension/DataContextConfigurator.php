<?php

namespace Oro\Bundle\LayoutBundle\Layout\Extension;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Moves the 'data' context variable to the context data collection.
 */
class DataContextConfigurator implements ContextConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        if (!$context->has('data')) {
            return;
        }
        $data = $context->get('data');
        if (!is_array($data)) {
            return;
        }

        foreach ($data as $key => $val) {
            if (!is_string($key)) {
                throw new \InvalidArgumentException(
                    sprintf('The data key "%s" must be a string, but "%s" given.', $key, gettype($key))
                );
            }
            if (!is_array($val)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The data item "%s" must be an array, but "%s" given.',
                        $key,
                        is_object($val) ? get_class($val) : gettype($val)
                    )
                );
            }

            $context->data()->set($key, $this->getDataIdentifier($key, $val), $this->getData($key, $val));
        }

        $context->remove('data');
    }

    /**
     * @param string $key
     * @param array  $val
     *
     * @return string
     */
    protected function getDataIdentifier($key, $val)
    {
        if (isset($val['id'])) {
            $identifier = $val['id'];
        } elseif (isset($val['identifier'])) {
            $identifier = $val['identifier'];
        } else {
            throw new \InvalidArgumentException(
                sprintf('The data item "%s" must have either "id" or "identifier" key.', $key)
            );
        }
        if (!is_string($identifier)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The data identifier for the data item "%s" must be a string, but "%s" given.',
                    $key,
                    is_object($identifier) ? get_class($identifier) : gettype($identifier)
                )
            );
        }

        return $identifier;
    }

    /**
     * @param string $key
     * @param array  $val
     *
     * @return mixed
     */
    protected function getData($key, $val)
    {
        if (!isset($val['data']) && !array_key_exists('data', $val)) {
            throw new \InvalidArgumentException(
                sprintf('The data item "%s" must have "data" key.', $key)
            );
        }

        return $val['data'];
    }
}
