<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoPgsql;

class PdoPgsqlTest extends AbstractPdoTest
{
    protected function setUp()
    {
        parent::setUp();

        $this->driver = new PdoPgsql();
    }
}
