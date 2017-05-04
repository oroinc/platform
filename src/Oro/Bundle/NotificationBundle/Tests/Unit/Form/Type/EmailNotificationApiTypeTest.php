<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\EventListener\BuildTemplateFormSubscriber;
use Oro\Bundle\NotificationBundle\Form\EventListener\AdditionalEmailsSubscriber;
use Oro\Bundle\NotificationBundle\Form\Type\EmailNotificationApiType;
use Oro\Component\Testing\Unit\EntityTrait;

class EmailNotificationApiTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var EmailNotificationApiType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configProvider;

    protected function setUp()
    {
        $buildTemplateListener = $this->getMockBuilder(BuildTemplateFormSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();

        $additionalEmailsListener = $this->getMockBuilder(AdditionalEmailsSubscriber::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configProvider->expects($this->once())
            ->method('getConfigs')
            ->will($this->returnValue([]));
        $router = $this->getMockBuilder('Symfony\Component\Routing\RouterInterface')->getMockForAbstractClass();

        $this->type = new EmailNotificationApiType(
            $buildTemplateListener,
            $additionalEmailsListener,
            $this->configProvider,
            $router,
            $this->getPropertyAccessor()
        );
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('emailnotification_api', $this->type->getName());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(BuildTemplateFormSubscriber::class));

        $builder->expects($this->at(1))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf(AdditionalEmailsSubscriber::class));

        $builder->expects($this->at(6))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\SoapBundle\Form\EventListener\PatchSubscriber'));

        $this->type->buildForm($builder, array());
    }
}
