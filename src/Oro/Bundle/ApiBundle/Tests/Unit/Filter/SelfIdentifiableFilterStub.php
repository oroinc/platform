<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface;

class SelfIdentifiableFilterStub extends ComparisonFilter implements SelfIdentifiableFilterInterface
{
    /** @var string[]|\Exception|null */
    private $foundFilterKeys;

    /**
     * @param string[]|\Exception|null $filterKeys
     */
    public function setFoundFilterKeys($filterKeys)
    {
        $this->foundFilterKeys = $filterKeys;
    }

    /**
     * {@inheritdoc}
     */
    public function searchFilterKeys(array $filterValues): array
    {
        if ($this->foundFilterKeys instanceof \Exception) {
            throw $this->foundFilterKeys;
        }

        if (null === $this->foundFilterKeys) {
            return [];
        }

        return $this->foundFilterKeys;
    }
}
