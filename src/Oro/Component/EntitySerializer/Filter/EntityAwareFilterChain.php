<?php

namespace Oro\Component\EntitySerializer\Filter;

/**
 * Loop through the filters and take the most restrictive result
 */
class EntityAwareFilterChain implements EntityAwareFilterInterface
{
    /** @var array|EntityAwareFilterInterface[] */
    protected $filters = [];

    /**
     * @param EntityAwareFilterInterface $filter
     * @param int                        $priority
     */
    public function addFilter(EntityAwareFilterInterface $filter, $priority = 0)
    {
        if (empty($this->filters[$priority])) {
            $this->filters[$priority] = [];
        }

        $this->filters[$priority][] = $filter;
    }

    /**
     * {@inheritdoc}
     */
    public function checkField($entity, $entityClass, $field)
    {
        // if no filters - nothing will be filtered
        $finalResult = static::FILTER_NOTHING;

        foreach ($this->filters as $filters) {
            foreach ($filters as $filter) {
                $result = $filter->checkField($entity, $entityClass, $field);

                if ($result < static::FILTER_NOTHING) {
                    $finalResult = $result;
                }
            }
        }

        return $finalResult;
    }
}
