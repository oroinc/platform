<?php

namespace Oro\Bundle\ReportBundle\Tests\Unit\Stub;

use Oro\Bundle\ReportBundle\Entity\Report;

class ReportStub extends Report
{
    public function setId($id)
    {
        $this->id = $id;
    }
}
