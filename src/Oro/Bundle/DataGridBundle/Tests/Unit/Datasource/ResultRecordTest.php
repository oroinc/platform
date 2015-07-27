<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\Datasource;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;

class ResultRecordTest extends \PHPUnit_Framework_TestCase
{
    public function testAddData()
    {
        $originalContainer = ['first' => 1];
        $additionalContainer = ['second' => 2];

        $resultRecord = new ResultRecord($originalContainer);
        $resultRecord->addData($additionalContainer);

        $this->assertAttributeContains($originalContainer, 'valueContainers', $resultRecord);
        $this->assertAttributeContains($additionalContainer, 'valueContainers', $resultRecord);

        $this->assertEquals($originalContainer['first'], $resultRecord->getValue('first'));
        $this->assertEquals($additionalContainer['second'], $resultRecord->getValue('second'));
    }
}
