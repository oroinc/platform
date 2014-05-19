<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\CalendarBundle\Validator\Constraints\DateEarlierThan;

class DateEarlierThanTest extends \PHPUnit_Framework_TestCase
{
    protected $field;
    protected $requiredOption;

    protected function setUp()
    {
        $this->field = 'field';
        $this->requiredOption = array($this->field => 'field-value');
    }

    public function testGetDefaultOption()
    {
        $constrains = new DateEarlierThan($this->requiredOption);
        $this->assertEquals($this->field, $constrains->getDefaultOption());
    }

    public function testGetRequiredOptions()
    {
        $constrains = new DateEarlierThan($this->requiredOption);
        $this->assertEquals(array($this->field), $constrains->getRequiredOptions());
    }
}
