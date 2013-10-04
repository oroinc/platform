<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Command;

class OroTranslationPackCommandTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {

    }

    public function tearDown()
    {

    }

    /**
     * Test configuration
     */
    public function testConfigure()
    {
        $methods = array('setName', 'setDefinition', 'setDescription', 'setHelp');
        $command = $this->getCommandMock($methods);

        foreach ($methods as $method) {
            $command->expects($this->once())->method($method)->will($this->returnSelf());
        }
        $this->callProtectedMethod($command, 'configure');
    }

    /**
     * @param mixed  $obj
     * @param string $methodName
     * @param array  $args
     *
     * @return mixed
     */
    protected function callProtectedMethod($obj, $methodName, $args = array())
    {
        $class  = new \ReflectionClass($obj);
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($obj, $args);
    }

    /**
     * Prepares command mock
     *
     * @param array $methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCommandMock($methods = array())
    {
        $commandMock = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Command\OroTranslationPackCommand')
            ->setMethods($methods)->disableOriginalConstructor()->getMock();

        return $commandMock;
    }
}
