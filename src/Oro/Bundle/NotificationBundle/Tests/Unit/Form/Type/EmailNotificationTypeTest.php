<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationType;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;

class EmailNotificationTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EmailNotificationType
     */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $listener = $this->getMockBuilder('Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $configId = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface');
        $configId->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('Oro\Bundle\UserBundle\Entity\User'));
        $config = $this->getMock('Oro\Bundle\EntityConfigBundle\Config\ConfigInterface');
        $config->expects($this->once())
            ->method('get')
            ->will($this->returnValue(OwnershipType::OWNER_TYPE_BUSINESS_UNIT));
        $config->expects($this->once())
            ->method('getId')
            ->will($this->returnValue($configId));
        $this->configProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([$config]));

        $this->type = new EmailNotificationType($listener, $this->configProvider);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('emailnotification', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->exactly(4))
            ->method('add');

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber'));

        $this->type->buildForm($builder, array());
    }
}
