<?php
namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmbeddedFormBundle\Form\Type\ChannelAwareFormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ChannelAwareFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructed()
    {
        new ChannelAwareFormType();
    }

    /**
     * @test
     */
    public function shouldBuildForm()
    {
        $options = ['channel_form_type' => 'entity'];

        /** @var \PHPUnit_Framework_MockObject_MockObject | FormBuilderInterface $builder */
        $builder = $this->getMock('\Symfony\Component\Form\FormBuilder', [], [], '', false);
        $builder->expects($this->once())
            ->method('add')
            ->with(
                'channel',
                $options['channel_form_type'],
                [
                    'class'    => 'OroIntegrationBundle:Channel',
                    'property' => 'name',
                    'multiple' => false
                ]
            )
            ->will($this->returnSelf());

        $formType = new ChannelAwareFormType();
        $formType->buildForm($builder, $options);
    }

    /**
     * @test
     */
    public function shouldSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject | OptionsResolverInterface $resolver */
        $resolver = $this->getMock('\Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['channel_form_type' => 'entity']);

        $formType = new ChannelAwareFormType();
        $formType->setDefaultOptions($resolver);
    }

    /**
     * @test
     */
    public function shouldReturnFormName()
    {
        $formType = new ChannelAwareFormType();

        $this->assertEquals('oro_channel_aware_form', $formType->getName());
    }
}
