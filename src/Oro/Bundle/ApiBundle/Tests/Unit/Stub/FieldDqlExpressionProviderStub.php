<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Stub;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Util\FieldDqlExpressionProviderInterface;

class FieldDqlExpressionProviderStub implements FieldDqlExpressionProviderInterface
{
    #[\Override]
    public function getFieldDqlExpression(QueryBuilder $qb, string $fieldPath): ?string
    {
        return null;
    }
}
