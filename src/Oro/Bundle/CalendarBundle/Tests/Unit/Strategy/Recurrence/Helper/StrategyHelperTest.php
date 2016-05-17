<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model\Recurrence\Helper;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Strategy\Recurrence\Helper\StrategyHelper;

class StrategyHelperTest extends \PHPUnit_Framework_TestCase
{
    /** @var StrategyHelper */
    protected $helper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validator;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();
        $this->helper = new StrategyHelper($this->validator);
    }

    public function testValidateRecurrence()
    {
        $recurrence = new Recurrence();

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($recurrence);

        $this->helper->validateRecurrence($recurrence);
    }
}
