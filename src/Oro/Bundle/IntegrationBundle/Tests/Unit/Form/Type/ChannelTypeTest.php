<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\IntegrationBundle\Form\Type\ChannelType;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;

class ChannelTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ChannelType */
    protected $type;

    /** @var TypesRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var  FormBuilder|\PHPUnit_Framework_MockObject_MockObject */
    protected $builder;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    /** @var /Doctrine\Common\Persistence\ObjectManager|\PHPUnit_Framework_MockObject_MockObject  */
    protected $objectManager;

    public function setUp()
    {
        $this->registry = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Manager\TypesRegistry')
            ->disableOriginalConstructor()->getMock();
        $this->builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()->getMock();
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->objectManager = $this->getMockBuilder('Doctrine\Common\Persistence\ObjectManager')
            ->disableOriginalConstructor()->getMock();

        $this->type = new ChannelType($this->registry, $this->securityFacade, $this->objectManager);
    }

    public function tearDown()
    {
        unset($this->type, $this->registry, $this->builder);
    }

    public function testBuildForm()
    {
        $this->builder->expects($this->at(0))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormSubscriber'));
        $this->builder->expects($this->at(1))
            ->method('addEventSubscriber')
            ->with(
                $this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\ChannelFormTwoWaySyncSubscriber')
            );
        $this->builder->expects($this->at(2))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\DefaultUserOwnerSubscriber'));
        $this->builder->expects($this->at(3))
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\IntegrationBundle\Form\EventListener\OrganizationSubscriber'));

        $this->type->buildForm($this->builder, array());
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
