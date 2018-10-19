<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\FormView;

class EmailTemplateSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var EmailTemplateSelectType
     */
    protected $type;

    /**
     * Setup test env
     */
    protected function setUp()
    {
        $this->type = new EmailTemplateSelectType();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with($this->isType('array'));
        $this->type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $this->assertEquals(Select2TranslatableEntityType::class, $this->type->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_email_template_list', $this->type->getName());
    }

    public function testFinishView()
    {
        $optionKey = 'testKey';

        $formConfigMock = $this->createMock('Symfony\Component\Form\FormConfigInterface');
        $formConfigMock->expects($this->exactly(3))
            ->method('getOption')
            ->will(
                $this->returnValueMap(
                    array(
                        array('depends_on_parent_field', null, $optionKey),
                        array('data_route', null, 'test'),
                        array('data_route_parameter', null, 'id'),
                    )
                )
            );

        $formMock = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->setMethods(array('getConfig'))
            ->getMock();
        $formMock->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue($formConfigMock));

        $formView = new FormView();
        $this->type->finishView($formView, $formMock, array());
        $this->assertArrayHasKey('depends_on_parent_field', $formView->vars);
        $this->assertEquals($optionKey, $formView->vars['depends_on_parent_field']);
        $this->assertArrayHasKey('data_route', $formView->vars);
        $this->assertEquals('test', $formView->vars['data_route']);
        $this->assertArrayHasKey('data_route_parameter', $formView->vars);
        $this->assertEquals('id', $formView->vars['data_route_parameter']);
    }
}
