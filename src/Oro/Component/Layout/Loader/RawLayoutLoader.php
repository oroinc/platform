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
 *                 'children' => [
 *                     'header' => [
 *                         'children' => [
 *                             'logo' => []
 *                         ]
 *                     ],
 *                 ]
 *             ]
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
     *
     * @return LayoutManipulatorInterface
     */
    public function load(array $config)
    {
        if (!$config) {
            return $this->layoutManipulator;
        }

        if (isset($config[self::LAYOUT_CONFIG_KEY])) {
            if (array_key_exists('items', $config[self::LAYOUT_CONFIG_KEY])) {
                $this->items = $config[self::LAYOUT_CONFIG_KEY]['items'];
            }
            if (array_key_exists('tree', $config[self::LAYOUT_CONFIG_KEY])) {
                foreach ($config[self::LAYOUT_CONFIG_KEY]['tree'] as $block => $configPart) {
                    $this->appendBlock($block, $configPart);
                }
            }
            if (array_key_exists('aliases', $config[self::LAYOUT_CONFIG_KEY])) {
                foreach ($config[self::LAYOUT_CONFIG_KEY]['aliases'] as $alias => $id) {
                    $this->layoutManipulator->addAlias($alias, $id);
                }
            }
        }

        return $this->layoutManipulator;
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

            if (isset($configPart['children']) && !empty($configPart['children'])) {
                foreach ($configPart['children'] as $childName => $childConfig) {
                    $this->appendBlock($childName, $childConfig, $block);
                }
            }
        }
    }
}
