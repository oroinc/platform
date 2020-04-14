<?php

namespace Oro\Component\Action\Tests\Unit\Action;

use Oro\Component\Action\Action\CreateDateTime;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Tests\Unit\Fixtures\ItemStub;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\PropertyAccess\PropertyPath;

class CreateDateTimeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var CreateDateTime
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    protected function setUp(): void
    {
        $this->contextAccessor = new ContextAccessor();
        $this->action = new CreateDateTime($this->contextAccessor);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown(): void
    {
        unset($this->contextAccessor, $this->action);
    }

    public function testInitializeExceptionNoAttribute()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Option "attribute" name parameter is required');

        $this->action->initialize(array());
    }

    public function testInitializeExceptionInvalidAttribute()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Option "attribute" must be valid property definition.');

        $this->action->initialize(array('attribute' => 'string'));
    }

    public function testInitializeExceptionInvalidTime()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage('Option "time" must be a string, boolean given.');

        $this->action->initialize(array('attribute' => new PropertyPath('test_attribute'), 'time' => true));
    }

    public function testInitializeExceptionInvalidTimezone()
    {
        $this->expectException(\Oro\Component\Action\Exception\InvalidParameterException::class);
        $this->expectExceptionMessage(
            'Option "timezone" must be a PropertyPath or string or instance of DateTimeZone, boolean given.'
        );

        $this->action->initialize(array('attribute' => new PropertyPath('test_attribute'), 'timezone' => true));
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param array      $options
     * @param mixed|null $expectedResult
     * @param array      $context
     */
    public function testExecute(array $options, $expectedResult = null, array $context = [])
    {
        $context = new ItemStub($context);
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
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC')),
                'context' => [],
            ),
            'with_time_and_timezone_string' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                    'timezone'  => 'Europe/London',
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('Europe/London')),
                'context' => [],
            ),
            'with_time_and_timezone_object' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                    'timezone'  => new \DateTimeZone('Europe/London'),
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('Europe/London')),
                'context' => [],
            ),
            'with_time_and_timezone_path' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'time'      => '2014-01-01 00:00:00',
                    'timezone'  => new PropertyPath('timeZoneNY'),
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('America/New_York')),
                'context' => ['timeZoneNY' => 'America/New_York'],
            )
        );
    }
}
