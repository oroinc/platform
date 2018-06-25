<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType as IntegrationType;
use Symfony\Component\Form\FormBuilder;

class IntegrationTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var IntegrationType */
    protected $type;

    /** @var  FormBuilder|\PHPUnit\Framework\MockObject\MockObject */
    protected $builder;

    public function setUp()
    {
        $this->builder  = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()->getMock();

        $subscribersNS      = 'Oro\\Bundle\\IntegrationBundle\\Form\\EventListener\\';
        $integrationFS          = $this->getMockBuilder($subscribersNS . 'ChannelFormSubscriber')
            ->disableOriginalConstructor()->getMock();
        $defaultUserOwnerFS = $this->getMockBuilder($subscribersNS . 'DefaultOwnerSubscriber')
            ->disableOriginalConstructor()->getMock();

        $this->type = new IntegrationType($defaultUserOwnerFS, $integrationFS);
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
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultOwnerSubscriber'));

        $this->type->buildForm($this->builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }
}
