<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailTemplateSelectTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailTemplateSelectType */
    private $type;

    protected function setUp(): void
    {
        $this->type = new EmailTemplateSelectType();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
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

        $formConfigMock = $this->createMock(FormConfigInterface::class);
        $formConfigMock->expects($this->exactly(5))
            ->method('getOption')
            ->willReturnMap([
                ['depends_on_parent_field', null, $optionKey],
                ['data_route', null, 'test'],
                ['data_route_parameter', null, 'id'],
                ['includeNonEntity', null, 0],
                ['includeSystemTemplates', null, 1]
            ]);

        $formMock = $this->createMock(Form::class);
        $formMock->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfigMock);

        $formView = new FormView();
        $this->type->finishView($formView, $formMock, []);
        $this->assertArrayHasKey('depends_on_parent_field', $formView->vars);
        $this->assertEquals($optionKey, $formView->vars['depends_on_parent_field']);
        $this->assertArrayHasKey('data_route', $formView->vars);
        $this->assertEquals('test', $formView->vars['data_route']);
        $this->assertArrayHasKey('data_route_parameter', $formView->vars);
        $this->assertEquals('id', $formView->vars['data_route_parameter']);
    }
}
