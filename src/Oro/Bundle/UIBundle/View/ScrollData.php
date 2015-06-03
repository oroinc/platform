<?php

namespace Oro\Bundle\UIBundle\View;

class ScrollData
{
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
            'title' => $title,
            'useSubBlockDivider' => $useSubBlockDivider,
            'subblocks' => [],
        ];

        if (null !== $priority) {
            $block['priority'] = $priority;
        }

        if (null !== $class) {
            $block['class'] = $class;
        }

        $this->data['dataBlocks'][] = $block;

        return $this->getLastKey($this->data['dataBlocks']);
    }

    /**
     * @param int $blockId
     * @param string|null $title
     * @return int
     */
    public function addSubBlock($blockId, $title = null)
    {
        $this->assertBlockDefined($blockId);

        $subBlock = ['data' => []];

        if (null !== $title) {
            $subBlock['title'] = $title;
        }

        $this->data['dataBlocks'][$blockId]['subblocks'][] = $subBlock;

        return $this->getLastKey($this->data['dataBlocks'][$blockId]['subblocks']);
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

        $this->data['dataBlocks'][$blockId]['subblocks'][$subBlockID]['data'][] = $html;

        return $this->getLastKey($this->data['dataBlocks'][$blockId]['subblocks'][$subBlockID]['data']);
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
        if (!array_key_exists($blockId, $this->data['dataBlocks'])) {
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

        if (!array_key_exists($subBlockId, $this->data['dataBlocks'][$blockId]['subblocks'])) {
            throw new \LogicException(sprintf('Subblock %s is not defined', $subBlockId));
        }
    }
}
