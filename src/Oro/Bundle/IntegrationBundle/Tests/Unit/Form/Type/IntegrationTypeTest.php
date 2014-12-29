<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilder;

use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType as IntegrationType;

class IntegrationTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var IntegrationType */
    protected $type;

    /** @var  FormBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    public function setUp()
    {
        $this->builder  = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()->getMock();

        $subscribersNS      = 'Oro\\Bundle\\IntegrationBundle\\Form\\EventListener\\';
        $integrationFS          = $this->getMockBuilder($subscribersNS . 'ChannelFormSubscriber')
            ->disableOriginalConstructor()->getMock();
        $defaultUserOwnerFS = $this->getMockBuilder($subscribersNS . 'DefaultUserOwnerSubscriber')
            ->disableOriginalConstructor()->getMock();
        $organizationFS     = $this->getMockBuilder($subscribersNS . 'OrganizationSubscriber')
            ->disableOriginalConstructor()->getMock();

        $this->type = new IntegrationType($defaultUserOwnerFS, $integrationFS, $organizationFS);
    }

    public function tearDown()
    {
        unset($this->type, $this->builder);
    }

    public function testBuildForm()
    {
        $this->builder->expects($this->at(0))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber'));
        $this->builder->expects($this->at(1))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber'));

        $this->type->buildForm($this->builder, []);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_integration_channel_form', $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('form', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->setDefaultOptions($resolver);
    }
}
