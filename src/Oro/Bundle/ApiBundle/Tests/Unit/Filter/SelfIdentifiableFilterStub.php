<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Filter;

use Oro\Bundle\ApiBundle\Filter\ComparisonFilter;
use Oro\Bundle\ApiBundle\Filter\SelfIdentifiableFilterInterface;

class SelfIdentifiableFilterStub extends ComparisonFilter implements SelfIdentifiableFilterInterface
{
    /** @var string[]|\Exception|null */
    private array|\Exception|null $foundFilterKeys = null;

    /**
     * @param string[]|\Exception|null $filterKeys
     */
    public function setFoundFilterKeys(array|\Exception|null $filterKeys): void
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

        return $this->foundFilterKeys ?? [];
    }
}
