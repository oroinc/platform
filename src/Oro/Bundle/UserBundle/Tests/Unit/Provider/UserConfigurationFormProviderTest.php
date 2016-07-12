<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;

use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;

class UserConfigurationFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configBag;

    /** @var FormFactoryInterface */
    protected $factory;
    
    /** @var  UserConfigurationFormProvider */
    protected $provider;
    
    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configBag = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigBag')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->factory = Forms::createFormFactoryBuilder()
            ->getFormFactory();
        
        $this->provider = new UserConfigurationFormProvider($this->configBag, $this->factory, $this->securityFacade);
    }

    protected function tearDown()
    {
        unset($this->securityFacade);
        unset($this->factory);
        unset($this->configBag);
        unset($this->provider);
    }

    public function testParentCheckboxLabelUpdate()
    {
        $this->provider->setParentCheckboxLabel('test label');
        $class = new \ReflectionClass($this->provider);
        $prop  = $class->getProperty('parentCheckboxLabel');
        $prop->setAccessible(true);
        $this->assertEquals('test label', $prop->getValue($this->provider));
    }
}
