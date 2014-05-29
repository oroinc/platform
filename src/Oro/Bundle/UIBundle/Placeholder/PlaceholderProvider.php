<?php

namespace Oro\Bundle\UIBundle\Placeholder;

use Oro\Bundle\UIBundle\Placeholder\Filter\PlaceholderFilterInterface;

class PlaceholderProvider
{
    /**
     * @var array
     */
    protected $placeholders;

    /**
     * @var PlaceholderFilterInterface[]
     */
    protected $filters;

    /**
     * @param array                        $placeholders
     * @param PlaceholderFilterInterface[] $filters
     */
    public function __construct(array $placeholders, array $filters)
    {
        $this->placeholders = $placeholders;
        $this->filters      = $filters;
    }

    /**
     * Get items by placeholder name
     *
     * @param string $placeholderName
     * @param array  $variables
     * @param string $blockName
     * @return array
     */
    public function getPlaceholderItems($placeholderName, array $variables, $blockName = null)
    {
        $result = array();
        if (isset($this->placeholders[$placeholderName]['items'])) {
            foreach ($this->placeholders[$placeholderName]['items'] as $item) {
                if (empty($blockName) || (isset($item['block']) && $item['block'] === $blockName)) {
                    $result[] = $item;
                }
            }
        }

        foreach ($this->filters as $filter) {
            $result = $filter->filter($result, $variables);
        }

        return $result;
    }

    /**
     * Get blocks by placeholder name
     *
     * @param string $placeholderName
     * @param array  $variables
     * @return array
     */
    public function getPlaceholderBlocks($placeholderName, array $variables)
    {
        $result = array();
        if (isset($this->placeholders[$placeholderName]['blocks'])) {
            $result = $this->placeholders[$placeholderName]['blocks'];
        }

        foreach ($this->filters as $filter) {
            $result = $filter->filter($result, $variables);
        }

        return $result;
    }
}
