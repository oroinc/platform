<?php

namespace Oro\Component\Layout\Loader;

use Oro\Component\Layout\LayoutManipulatorInterface;

/**
 * Example of config
 * $config = [
 *     'oro_layout' => [
 *         'items' => [
 *             'root' => ['type' => 'root'],
 *             'header' => ['type' => 'header'],
 *             'logo' => ['type' => 'logo', 'options' => ['title' => 'test']],
 *         ],
 *         'tree' => [
 *             'root' => [
 *                 'header' => [
 *                     'logo' => []
 *                 ]
 *             ]
 *         ],
 *         'aliases' => [
 *             'root_alias' => 'root',
 *             'logo_1' => 'logo',
 *             'logo_2' => 'logo'
 *         ]
 *     ]
 * ];
 *
 */
class RawLayoutLoader
{
    const LAYOUT_CONFIG_KEY = 'oro_layout';

    /** @var array */
    protected $items = [];

    /** @var LayoutManipulatorInterface */
    protected $layoutManipulator;

    public function __construct(LayoutManipulatorInterface $layoutManipulator)
    {
        $this->layoutManipulator = $layoutManipulator;
    }

    /**
     * @param array $config Full config from merged configuration files
     */
    public function load(array $config)
    {
        if (!isset($config[self::LAYOUT_CONFIG_KEY])) {
            return;
        }

        $layoutConfig = $config[self::LAYOUT_CONFIG_KEY];

        if (isset($layoutConfig['items'])) {
            $this->items = $layoutConfig['items'];
        }

        if (isset($layoutConfig['tree'])) {
            foreach ($layoutConfig['tree'] as $block => $configPart) {
                $this->appendBlock($block, $configPart);
            }
        }

        if (isset($layoutConfig['aliases'])) {
            foreach ($layoutConfig['aliases'] as $alias => $id) {
                $this->layoutManipulator->addAlias($alias, $id);
            }
        }
    }

    /**
     * @param string      $block
     * @param array       $configPart
     * @param string|null $parentBlock
     */
    protected function appendBlock($block, $configPart, $parentBlock = null)
    {
        if (isset($this->items[$block])) {
            $this->layoutManipulator->add(
                $block,
                $parentBlock,
                isset($this->items[$block]['type']) ? $this->items[$block]['type'] : null,
                isset($this->items[$block]['options']) ? $this->items[$block]['options'] : []
            );

            if (is_array($configPart)) {
                foreach ($configPart as $childName => $childConfig) {
                    $this->appendBlock($childName, $childConfig, $block);
                }
            }
        }
    }
}
