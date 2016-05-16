<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Element;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use Oro\Bundle\TestFrameworkBundle\Behat\Element\OroElementFactory;

class OroElementFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessageRegExp /Could not find element with "\w+" name/
     */
    public function testCreateElementException()
    {
        $this->getElementFactory()->createElement('someElement');
    }

    public function testCreateElement()
    {
        $class = 'Oro\Bundle\TestFrameworkBundle\Behat\Element\Element';
        $element = $this->getElementFactory([
            'Test Oro Behat Element' => [
                'class' => $class,
                'selector' => 'body'
            ]
        ])->createElement('Test Oro Behat Element');

        $this->assertInstanceOf($class, $element);
    }

    /**
     * @param array $configuration
     * @return \PHPUnit_Framework_MockObject_MockObject|OroElementFactory
     */
    protected function getElementFactory(array $configuration = [])
    {
        $session = new Session(
            $this->getMock('Behat\Mink\Driver\DriverInterface'),
            $this->getMock('Behat\Mink\Selector\SelectorsHandler')
        );
        $mink = new Mink(['default' => $session]);
        $mink->setDefaultSessionName('default');
        $factory = new OroElementFactory($mink, $configuration);

        return $factory;
    }
}
