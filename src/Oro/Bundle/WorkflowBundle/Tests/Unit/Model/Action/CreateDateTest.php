<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\Action;

use Symfony\Component\PropertyAccess\PropertyPath;

use Oro\Bundle\WorkflowBundle\Model\Action\CreateDate;
use Oro\Bundle\EntityBundle\Tests\Unit\ORM\Stub\ItemStub;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CreateDateTest extends \PHPUnit_Framework_TestCase
{
    const TIMEZONE = 'Europe/London';

    /**
     * @var CreateDate
     */
    protected $action;

    /**
     * @var ContextAccessor
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    protected function setUp()
    {
        $this->contextAccessor = new ContextAccessor();

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings->expects($this->any())
            ->method('getTimeZone')
            ->will($this->returnValue(self::TIMEZONE));

        $this->action = new CreateDate($this->contextAccessor, $this->localeSettings);
    }

    protected function tearDown()
    {
        unset($this->contextAccessor);
        unset($this->localeSettings);
        unset($this->action);
    }

    /**
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException
     * @expectedExceptionMessage Option "date" must be a string, boolean given.
     */
    public function testInitializeExceptionInvalidTime()
    {
        $this->action->initialize(array('attribute' => new PropertyPath('test_attribute'), 'date' => true));
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
            'without_date' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                ),
            ),
            'with_date' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'date'      => '2014-01-01',
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC'))
            ),
            'with_datetime' => array(
                'options' => array(
                    'attribute' => new PropertyPath('test_attribute'),
                    'date'      => '2014-01-01 12:12:12',
                ),
                'expectedResult' => new \DateTime('2014-01-01 00:00:00', new \DateTimeZone('UTC'))
            ),
        );
    }
}
