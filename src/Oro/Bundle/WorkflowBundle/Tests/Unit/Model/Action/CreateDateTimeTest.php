<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Action\CreateDateTime;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CreateDateTimeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CreateDateTime
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new CreateDateTime($this->contextAccessor);
    }

    protected function tearDown()
    {
        unset($this->contextAccessor);
        unset($this->action);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Option "attribute" name parameter is required
     */
    public function testInitializeExceptionNoAttribute()
    {
        $this->action->initialize(array());
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Option "attribute" must be valid property definition.
     */
    public function testInitializeExceptionInvalidAttribute()
    {
        $this->action->initialize(array('attribute' => 'string'));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Option "time" must be a string, boolean given.
     */
    public function testInitializeExceptionInvalidTime()
    {
        $this->action->initialize(array('attribute' => new PropertyPath('test_attribute'), 'time' => true));
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Option "timezone" must be a string or instance of DateTimeZone, boolean given.
     */
    public function testInitializeExceptionInvalidTimezone()
    {
        $this->action->initialize(array('attribute' => new PropertyPath('test_attribute'), 'timezone' => true));
    }

    /**
     * @dataProvider executeDataProvider
     */
    public function testExecute(array $options, $expectedResult = null)
    {
        $context = new ItemStub(array());
        $attributeName = (string)$options['attribute'];
        $this->action->initialize($options);
        $this->action->execute($context);
        $this->assertNotNull($context->$attributeName);
        $this->assertInstanceOf('DateTime', $context->$attributeName);

        if ($expectedResult) {
            $this->assertEquals($expectedResult, $context->$attributeName);
        }
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return array(
            'without_time_and_timezone' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                ),
            ),
            'with_time' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC'))
            ),
            'with_time_and_timezone_string' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                    'timezone'  => 'Europe/London',
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('Europe/London'))
            ),
            'with_time_and_timezone_object' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                    'timezone'  => new \DateTimeZone('Europe/London'),
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('Europe/London'))
            )
        );
    }
}
