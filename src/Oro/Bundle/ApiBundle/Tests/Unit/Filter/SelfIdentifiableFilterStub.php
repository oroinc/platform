<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface;

class SelfIdentifiableFilterStub extends ComparisonFilter implements SelfIdentifiableFilterInterface
{
    /** @var string|\Exception|null */
    protected $foundFilterKey;

    /**
     * @param string|\Exception|null $filterKey
     */
    public function setFoundFilterKey($filterKey)
    {
        $this->foundFilterKey = $filterKey;
    }

    /**
     * {@inheritdoc}
     */
    public function searchFilterKey(array $filterValues)
    {
        if ($this->foundFilterKey instanceof \Exception) {
            throw $this->foundFilterKey;
        }

        return $this->foundFilterKey;
    }
}
