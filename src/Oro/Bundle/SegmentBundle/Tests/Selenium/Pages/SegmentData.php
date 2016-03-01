<?php

namespace Oro\Bundle\SegmentBundle\Tests\Selenium\Pages;

use Oro\Bundle\TestFrameworkBundle\Pages\AbstractPageFilteredGrid;

class SegmentData extends AbstractPageFilteredGrid
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

    public function refreshSegment()
    {
        $this->test->byXPath(
            "//div[@class='pull-right title-buttons-container']//a[@title='Refresh segment']"
        )->click();
        $this->test->byXPath("//div[div[contains(., 'Confirm action')]]//a[contains(., 'Yes')]")->click();
        $this->waitPageToLoad();
        //sleep(1);
        $this->waitForAjax();

        return $this;
    }
}
