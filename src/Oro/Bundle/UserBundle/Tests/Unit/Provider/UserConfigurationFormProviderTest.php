<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigBag;
use Oro\Bundle\UserBundle\Provider\UserConfigurationFormProvider;

class UserConfigurationFormProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $authorizationChecker;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configBag;

    /** @var FormFactoryInterface */
    protected $factory;
    
    /** @var  UserConfigurationFormProvider */
    protected $provider;
    
    protected function setUp()
    {
        $this->configBag = $this->createMock(ConfigBag::class);
        $this->factory = Forms::createFormFactoryBuilder()->getFormFactory();
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->provider = new UserConfigurationFormProvider(
            $this->configBag,
            $this->factory,
            $this->authorizationChecker
        );
    }

    protected function tearDown()
    {
        unset($this->authorizationChecker);
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
