<?php

namespace Oro\Component\Layout\Loader;

use Oro\Component\Layout\LayoutManipulatorInterface;

class RawLayoutLoader
{
    const LAYOUT_CONFIG_KEY = 'oro_layout';

    /** @var array */
    protected $items = [];

    /**
     * @param LayoutManipulatorInterface  $manipulator
     * @param array                       $config      Full config from merged configuration files
     * @return LayoutManipulatorInterface
     */
    public function load(LayoutManipulatorInterface $manipulator, array $config)
    {
        if (!$config) {
            return $manipulator;
        }

        if (isset($config[self::LAYOUT_CONFIG_KEY])) {
            if (array_key_exists('items', $config)) {
                $this->items = $config['items'];
            }
            if (array_key_exists('tree', $config)) {
                foreach ($config['tree'] as $block => $configPart) {
                    $this->appendBlock($manipulator, $block, $configPart);
                }
            }
            if (array_key_exists('aliases', $config)) {
                foreach ($config['aliases'] as $id => $aliasConfig) {
                    $manipulator->addAlias(isset($aliasConfig['alias']) ? : null, $id);
                }
            }
        }

        return $manipulator;
    }

    /**
     * @param LayoutManipulatorInterface $manipulator
     * @param string                     $block
     * @param array                      $configPart
     * @param string|null                $parentBlock
     */
    protected function appendBlock(LayoutManipulatorInterface $manipulator, $block, $configPart, $parentBlock = null)
    {
        if (isset($this->items[$block])) {
            $manipulator->add(
                $block,
                $parentBlock,
                $this->items[$block]['type'] ? : null,
                $this->items[$block]['options'] ? : null
            );

            if (!empty($configPart['children'])) {
                foreach ($configPart['children'] as $childName => $childConfig) {
                    $this->appendBlock($manipulator, $childName, $childConfig, $block);
                }
            }
        }
    }
}
