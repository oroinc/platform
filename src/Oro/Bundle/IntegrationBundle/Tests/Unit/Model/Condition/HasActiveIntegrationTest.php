<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Model\Condition;

use Oro\Bundle\IntegrationBundle\Model\Condition\HasActiveIntegration;

class HasActiveIntegrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $registry;

    /**
     * @var HasActiveIntegration
     */
    protected $condition;

    protected function setUp()
    {
        $this->registry = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->getMock();
        $this->condition = new HasActiveIntegration($this->registry);
    }

    /**
     * @dataProvider failingOptionsDataProvider
     * @expectedException \Oro\Component\ConfigExpression\Exception\InvalidArgumentException
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

    public function testEvaluate()
    {
        $context = [];
        $type = 'testType';
        $entity = new \stdClass();

        $this->condition->initialize([$type]);

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

        $this->assertTrue($this->condition->evaluate($context));
    }
}
