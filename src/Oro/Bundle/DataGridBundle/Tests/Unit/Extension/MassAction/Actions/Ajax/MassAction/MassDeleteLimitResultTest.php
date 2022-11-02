<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Extension\MassAction\Actions\Ajax\MassAction;

use Oro\Bundle\DataGridBundle\Extension\MassAction\Actions\Ajax\MassDelete\MassDeleteLimitResult;

class MassDeleteLimitResultTest extends \PHPUnit\Framework\TestCase
{
    public function testLimitResultGetters()
    {
        $result = new MassDeleteLimitResult(10, 5, 200);

        self::assertEquals(10, $result->getSelected());
        self::assertEquals(5, $result->getDeletable());
        self::assertEquals(200, $result->getMaxLimit());
    }

    public function testDefaultValueOfMaxLimit()
    {
        $result = new MassDeleteLimitResult(10, 5);

        self::assertEquals(100, $result->getMaxLimit());
    }
}
