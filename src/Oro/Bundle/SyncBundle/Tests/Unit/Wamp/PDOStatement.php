<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Wamp;

/**
 * Class Mock PDOStatement
 *
 * @package Oro\Bundle\SyncBundle\Tests\Unit\Wamp
 */
class PDOStatement extends \PDOStatement
{
    public function execute($bound_input_params = null)
    {
        return true;
    }
}
