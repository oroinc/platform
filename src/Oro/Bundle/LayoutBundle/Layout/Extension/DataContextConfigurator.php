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
            if (is_array($val)) {
                $context->data()->set($key, $this->getData($key, $val));
            } else {
                $context->data()->set($key, $val);
            }
        }

        $context->remove('data');
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
