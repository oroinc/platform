<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Stub;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierOptionsAwareInterface;

class QueryModifierWithOptionsStub implements QueryModifierInterface, QueryModifierOptionsAwareInterface
{
    #[\Override]
    public function modify(QueryBuilder $qb, bool $skipRootEntity): void
    {
    }

    #[\Override]
    public function setOptions(?array $options): void
    {
    }
}
