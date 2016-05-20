<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Import;

use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Model\Import\ProcessImportResult;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProcessImportResultTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * @dataProvider accessorsData
     * @param $property
     * @param $value
     */
    public function testAccessors($property, $value)
    {
        $this->assertPropertyGetterReturnsSetValue(new ProcessImportResult(), $property, $value);
    }

    /**
     * @return array
     */
    public function accessorsData()
    {
        return [
            [
                'definitions',
                [new ProcessDefinition()],
            ],
            [
                'triggers',
                [new ProcessTrigger()]
            ],
            [
                'schedules',
                [new Schedule()]
            ]
        ];
    }
}
