<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\Model;

use Oro\Bundle\ReminderBundle\Model\SendProcessorRegistry;

class SendProcessorRegistryTest extends \PHPUnit\Framework\TestCase
{
    const FOO_METHOD = 'foo';
    const FOO_LABEL  = 'foo_label';
    const BAR_METHOD = 'bar';
    const BAR_LABEL  = 'bar_label';

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject[]
     */
    protected $processors;

    /**
     * @var SendProcessorRegistry
     */
    protected $registry;

    protected function setUp()
    {
        $this->processors = array();

        $this->processors[self::FOO_METHOD] = $this->getMockProcessor(self::FOO_METHOD, self::FOO_LABEL);
        $this->processors[self::BAR_METHOD] = $this->getMockProcessor(self::BAR_METHOD, self::BAR_LABEL);

        $this->registry = new SendProcessorRegistry($this->processors);
    }

    public function testGetProcessors()
    {
        $this->assertEquals(
            array(
                self::FOO_METHOD => $this->processors[self::FOO_METHOD],
                self::BAR_METHOD => $this->processors[self::BAR_METHOD]
            ),
            $this->registry->getProcessors()
        );
    }

    public function testGetProcessor()
    {
        $this->assertEquals($this->processors[self::FOO_METHOD], $this->registry->getProcessor(self::FOO_METHOD));
        $this->assertEquals($this->processors[self::BAR_METHOD], $this->registry->getProcessor(self::BAR_METHOD));
    }

    public function testGetProcessorLabels()
    {
        $this->assertEquals(
            [
                self::FOO_LABEL => self::FOO_METHOD,
                self::BAR_LABEL => self::BAR_METHOD,
            ],
            $this->registry->getProcessorLabels()
        );
    }

    /**
     * @expectedException \Oro\Bundle\ReminderBundle\Exception\MethodNotSupportedException
     * @expectedExceptionMessage Reminder method "not_exist" is not supported.
     */
    public function testGetProcessorFails()
    {
        $this->registry->getProcessor('not_exist');
    }

    protected function getMockProcessor($name, $label)
    {
        $result = $this->createMock('Oro\\Bundle\\ReminderBundle\\Model\\SendProcessorInterface');
        $result->expects($this->once())
            ->method('getName')
            ->will($this->returnValue($name));

        $result->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($label));

        return $result;
    }
}
