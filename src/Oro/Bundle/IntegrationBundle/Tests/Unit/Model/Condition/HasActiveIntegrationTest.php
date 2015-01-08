<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\IntegrationBundle\Model\Condition\HasActiveIntegration;

class HasActiveIntegrationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var HasActiveIntegration
     */
    protected $condition;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\ContextAccessor')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->getMock();
        $this->condition = new HasActiveIntegration($this->contextAccessor, $this->registry);
    }

    /**
     * @dataProvider failingOptionsDataProvider
     * @expectedException \Oro\Bundle\WorkflowBundle\Exception\ConditionException
     * @param array $options
     */
    public function testInitializeException(array $options)
    {
        $this->condition->initialize($options);
    }

    /**
     * @return array
     */
    public function failingOptionsDataProvider()
    {
        return [
            'empty' => [[]],
            'accordion option set' => [['\\', '//']]
        ];
    }

    public function testInitialize()
    {
        $options = ['test'];
        $this->assertSame($this->condition, $this->condition->initialize($options));
    }

    public function testIsAllowed()
    {
        $context = [];
        $options = ['test'];
        $type = 'testType';
        $entity = new \stdClass();

        $this->condition->initialize($options);
        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, 'test')
            ->will($this->returnValue($type));

        $repository = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Entity\Repository\ChannelRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('getConfiguredChannelsForSync')
            ->with($type, true)
            ->will($this->returnValue([$entity]));
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroIntegrationBundle:Channel')
            ->will($this->returnValue($repository));

        $this->assertTrue($this->condition->isAllowed($context));
    }
}
