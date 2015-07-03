<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber;
use Oro\Bundle\UserBundle\Form\Type\ChangePasswordType;

class ChangePasswordTypeTest extends FormIntegrationTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ChangePasswordSubscriber */
    protected $subscriber;

    /** @var ChangePasswordType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->subscriber = $this->getMockBuilder('Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ChangePasswordType($this->subscriber);
    }

    protected function tearDown()
    {
        unset($this->subscriber, $this->type);
    }

    /**
     * Test buildForm
     */
    public function testBuildForm()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface $builder */
        $builder = $this->getMock('Symfony\Component\Form\Test\FormBuilderInterface');
        $options = [
            'current_password_label' => 'label',
            'plain_password_invalid_message' => 'label',
            'first_options_label' => 'label',
            'second_options_label' => 'label'
        ];

        $builder->expects($this->once())
            ->method('addEventSubscriber')
            ->with($this->isInstanceOf('Oro\Bundle\UserBundle\Form\EventListener\ChangePasswordSubscriber'));

        $builder->expects($this->exactly(2))
            ->method('add')
            ->will($this->returnSelf());

        $this->type->buildForm($builder, $options);
    }

    /**
     * Test defaults
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                $this->logicalAnd(
                    $this->isType('array'),
                    $this->arrayHasKey('current_password_label'),
                    $this->arrayHasKey('plain_password_invalid_message'),
                    $this->arrayHasKey('first_options_label'),
                    $this->arrayHasKey('second_options_label')
                )
            );
        $this->type->setDefaultOptions($resolver);
    }

    /**
     * Test name
     */
    public function testName()
    {
        $this->assertEquals('oro_change_password', $this->type->getName());
    }
}
