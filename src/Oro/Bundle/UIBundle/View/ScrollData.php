<?php

namespace Oro\Bundle\UIBundle\View;

class ScrollData
{
    const TITLE = 'title';
    const USE_SUB_BLOCK_DIVIDER = 'useSubBlockDivider';
    const SUB_BLOCKS = 'subblocks';
    const PRIORITY = 'priority';
    const BLOCK_CLASS = 'class';
    const DATA_BLOCKS = 'dataBlocks';
    const DATA = 'data';

    /**
     * @var array
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setData($data);
    }

    /**
     * @param string $title
     * @param int|null $priority
     * @param string|null $class
     * @param bool $useSubBlockDivider
     * @return int
     */
    public function addBlock($title, $priority = null, $class = null, $useSubBlockDivider = true)
    {
        $block = $this->fillBlockData($title, $priority, $class, $useSubBlockDivider);
        $this->data[self::DATA_BLOCKS][] = $block;

        return $this->getLastKey($this->data[self::DATA_BLOCKS]);
    }

    /**
     * @param string|int $blockId
     * @return bool
     */
    public function hasBlock($blockId): bool
    {
        return isset($this->data[self::DATA_BLOCKS][$blockId]);
    }

    /**
     * @param int|string $blockId
     * @param string|null $title
     * @param int|null $priority
     * @param string|null $class
     * @param bool $useSubBlockDivider
     * @throws \InvalidArgumentException
     */
    public function changeBlock($blockId, $title = null, $priority = null, $class = null, $useSubBlockDivider = null)
    {
        if (!$this->hasBlock($blockId)) {
            throw new \InvalidArgumentException(sprintf('Block with id "%s" has not been found', $blockId));
        }

        $block = $this->data[self::DATA_BLOCKS][$blockId];
        $block = $this->setBlockData($block, $title, $priority, $class, $useSubBlockDivider);

        $this->data[self::DATA_BLOCKS][$blockId] = $block;
    }

    /**
     * @param $title
     * @param null $priority
     * @param null $class
     * @param bool $useSubBlockDivider
     * @param array $block
     * @return array
     */
    private function fillBlockData(
        $title,
        $priority = null,
        $class = null,
        $useSubBlockDivider = true,
        array $block = []
    ) {
        if (!isset($block[self::SUB_BLOCKS])) {
            $block[self::SUB_BLOCKS] = [];
        }

        return $this->setBlockData($block, $title, $priority, $class, $useSubBlockDivider);
    }

    /**
     * @param array $block
     * @param string|null $title
     * @param int|null $priority
     * @param string|null $class
     * @param bool|null $useSubBlockDivider
     * @return array
     */
    private function setBlockData(array $block, $title, $priority, $class, $useSubBlockDivider)
    {
        if (null !== $title) {
            $block[self::TITLE] = $title;
        }

        if (null !== $useSubBlockDivider) {
            $block[self::USE_SUB_BLOCK_DIVIDER] = $useSubBlockDivider;
        }

        if (null !== $priority) {
            $block[self::PRIORITY] = $priority;
        }

        if (null !== $class) {
            $block[self::BLOCK_CLASS] = $class;
        }

        return $block;
    }

    /**
     * Adds named block if it doesn't exists or overrides existing block info.
     *
     * @param string $blockName
     * @param mixed|string $title
     * @param int|null $priority
     * @param string|null $class
     * @param bool $useSubBlockDivider
     */
    public function addNamedBlock($blockName, $title, $priority = null, $class = null, $useSubBlockDivider = true)
    {
        $block = isset($this->data[self::DATA_BLOCKS][$blockName]) ? $this->data[self::DATA_BLOCKS][$blockName] : [];

        $block = $this->fillBlockData($title, $priority, $class, $useSubBlockDivider, $block);
        $this->data[self::DATA_BLOCKS][$blockName] = $block;
    }

    /**
     * @param string $blockId
     */
    public function removeNamedBlock($blockId)
    {
        unset($this->data[self::DATA_BLOCKS][$blockId]);
    }

    /**
     * @param int|string $blockId
     * @param string|null $title
     * @return int
     */
    public function addSubBlock($blockId, $title = null)
    {
        $this->assertBlockDefined($blockId);

        $subBlock = [self::DATA => []];

        if (null !== $title) {
            $subBlock[self::TITLE] = $title;
        }

        $this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS][] = $subBlock;

        return $this->getLastKey($this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS]);
    }

    /**
     * @param int|string $blockId
     * @param string|null $title
     * @return int
     */
    public function addSubBlockAsFirst($blockId, $title = null)
    {
        $this->assertBlockDefined($blockId);

        $subBlock = [self::DATA => []];

        if (null !== $title) {
            $subBlock[self::TITLE] = $title;
        }

        array_unshift($this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS], $subBlock);

        return 0;
    }

    /**
     * @param int|string $blockId
     * @param int $subBlockID
     * @param string $html
     * @param string|null $fieldName
     * @return int
     */
    public function addSubBlockData($blockId, $subBlockID, $html, $fieldName = null)
    {
        $this->assertSubBlockDefined($blockId, $subBlockID);

        if ($fieldName !== null) {
            $this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS][$subBlockID][self::DATA][$fieldName] = $html;
        } else {
            $this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS][$subBlockID][self::DATA][] = $html;
        }

        return $this->getLastKey($this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS][$subBlockID][self::DATA]);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return ScrollData
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @param string $fieldId
     * @return bool
     */
    public function hasNamedField($fieldId)
    {
        foreach ($this->data[self::DATA_BLOCKS] as $currentBlockId => &$blockData) {
            foreach ($blockData[self::SUB_BLOCKS] as $subblockId => &$subblock) {
                if (isset($subblock[self::DATA][$fieldId])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function moveFieldToBlock($fieldId, $blockId)
    {
        if (!isset($this->data[self::DATA_BLOCKS][$blockId])) {
            return;
        }

        foreach ($this->data[self::DATA_BLOCKS] as $currentBlockId => &$blockData) {
            foreach ($blockData[self::SUB_BLOCKS] as $subblockId => &$subblock) {
                if (isset($subblock[self::DATA][$fieldId])) {
                    $fieldData = $subblock[self::DATA][$fieldId];

                    if ($blockId != $currentBlockId) {
                        unset($subblock[self::DATA][$fieldId]);

                        $subblockIds = $this->getSubblockIds($blockId);
                        if (empty($subblockIds)) {
                            $subblockId = $this->addSubBlock($blockId);
                        } else {
                            $subblockId = reset($subblockIds);
                        }

                        $this->addSubBlockData($blockId, $subblockId, $fieldData, $fieldId);
                        break;
                    }
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getBlockIds()
    {
        return array_keys($this->data[self::DATA_BLOCKS]);
    }

    /**
     * @param string|int $blockId
     * @return array
     */
    public function getSubblockIds($blockId)
    {
        if (!isset($this->data[self::DATA_BLOCKS][$blockId])) {
            return [];
        }

        return array_keys($this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS]);
    }

    /**
     * @param array $array
     * @return mixed
     */
    protected function getLastKey(array $array)
    {
        $keys = array_keys($array);

        return end($keys);
    }

    /**
     * @param int|string $blockId
     */
    protected function assertBlockDefined($blockId)
    {
        if (!array_key_exists($blockId, $this->data[self::DATA_BLOCKS])) {
            throw new \LogicException(sprintf('Block %s is not defined', $blockId));
        }
    }

    /**
     * @param int $blockId
     * @param int $subBlockId
     */
    protected function assertSubBlockDefined($blockId, $subBlockId)
    {
        $this->assertBlockDefined($blockId);

        if (!array_key_exists($subBlockId, $this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS])) {
            throw new \LogicException(sprintf('Subblock %s is not defined', $subBlockId));
        }
    }
}
