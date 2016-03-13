<?php

namespace Oro\Bundle\ApiBundle\Config;


class ActionsConfigLoader extends AbstractConfigLoader implements ConfigLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config)
    {
        $actions = new ActionsConfig();
        foreach ($config as $key => $value) {
            $actions->set($key, $value);
        }
        //$actions->set('delete', 'dwq');

//        foreach ($config as $key => $value) {
//            if (isset($this->methodMap[$key])) {
//                $this->callSetter($filters, $this->methodMap[$key], $value);
//            } elseif (ConfigUtil::FIELDS === $key) {
//                $this->loadFields($filters, $value);
//            } else {
//                $this->setValue($filters, $key, $value);
//            }
//        }

        return $actions;
    }
}
