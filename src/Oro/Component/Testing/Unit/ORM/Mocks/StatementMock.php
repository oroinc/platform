<?php

namespace Oro\Component\Testing\Unit\ORM\Mocks;

use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;

/**
 * This class is a clone of namespace Doctrine\Tests\Mocks\StatementMock that is excluded from doctrine
 * package since v2.4.
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class StatementMock implements Statement
{
    #[\Override]
    public function bindValue($param, $value, $type = null)
    {
    }

    #[\Override]
    public function bindParam($param, &$variable, $type = null, $length = null)
    {
    }

    #[\Override]
    public function execute($params = null): Result
    {
        return new ResultMock();
    }
}
