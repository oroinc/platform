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
        $block = [
            self::TITLE => $title,
            self::USE_SUB_BLOCK_DIVIDER => $useSubBlockDivider,
            self::SUB_BLOCKS => [],
        ];

        if (null !== $priority) {
            $block[self::PRIORITY] = $priority;
        }

        if (null !== $class) {
            $block[self::BLOCK_CLASS] = $class;
        }

        $this->data[self::DATA_BLOCKS][] = $block;

        return $this->getLastKey($this->data[self::DATA_BLOCKS]);
    }

    /**
     * @param int $blockId
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
     * @param $blockId
     * @param $subBlockID
     * @param $html
     * @return int
     */
    public function addSubBlockData($blockId, $subBlockID, $html)
    {
        $this->assertSubBlockDefined($blockId, $subBlockID);

        $this->data[self::DATA_BLOCKS][$blockId][self::SUB_BLOCKS][$subBlockID][self::DATA][] = $html;

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
     * @param array $array
     * @return mixed
     */
    protected function getLastKey(array $array)
    {
        $keys = array_keys($array);

        return end($keys);
    }

    /**
     * @param int $blockId
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
