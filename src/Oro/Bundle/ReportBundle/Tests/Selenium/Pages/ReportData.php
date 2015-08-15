<?php

namespace Oro\Bundle\ReportBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

class ReportData extends AbstractPageFilteredGrid
{
    public function open($entityData = array())
    {
        return;
    }

    public function entityView()
    {
        return $this;
    }

    public function entityNew()
    {
        return $this;
    }
}
