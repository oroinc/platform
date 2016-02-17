<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\FormProvider;

class FormProviderTest extends \PHPUnit_Framework_TestCase
{
    const FORM_TYPE = 'test_type';
    const ACTION_ROUTE = 'test_route';

    /**
     * @var FormProvider
     */
    protected $provider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Symfony\Component\Form\FormFactoryInterface
     */
    protected $formFactory;

    public function setUp()
    {
        $this->form = $this->getMockForAbstractClass('Symfony\Component\Form\FormInterface');

        $this->formFactory = $this->getMockForAbstractClass('Symfony\Component\Form\FormFactoryInterface');

        $this->provider = new FormProvider($this->formFactory);
    }

    public function testGetData()
    {
        $this->provider->setFormType(self::FORM_TYPE);
        $this->provider->setActionRoute(self::ACTION_ROUTE);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE, null, [])
            ->willReturn($this->form);

        $context = $this->getMockForAbstractClass('Oro\Component\Layout\ContextInterface');

        $data = $this->provider->getData($context);
        $this->assertInstanceOf('Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor', $data);
        $this->assertEquals($this->form, $data->getForm());
    }

    public function testGetForm()
    {
        $this->provider->setFormType(self::FORM_TYPE);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(self::FORM_TYPE, null, [])
            ->willReturn($this->form);

        $form = $this->provider->getForm([]);
        $this->assertEquals($this->form, $form);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Oro\Bundle\LayoutBundle\Layout\DataProvider\FormProvider::formType should be defined
     */
    public function testGetFormWithoutFormType()
    {
        $this->provider->getForm();
    }
}
