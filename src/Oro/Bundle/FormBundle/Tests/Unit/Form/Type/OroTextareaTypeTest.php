<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroTextareaType;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroTextareaTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OroTextareaType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|HtmlTagHelper
     */
    protected $htmlTagHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);
        $this->formType = new OroTextareaType($this->htmlTagHelper);
    }

    public function testGetParent()
    {
        $this->assertEquals(TextareaType::class, $this->formType->getParent());
    }

    public function testGetName()
    {
        $this->assertEquals('oro_textarea', $this->formType->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals($this->formType->getName(), $this->formType->getBlockPrefix());
    }

    public function testBuildForm()
    {
        /** @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->atLeastOnce())
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, function () {
            });
        $this->formType->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit_Framework_MockObject_MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->atLeastOnce())
            ->method('setDefaults')
            ->with([
                'strip_tags' => false,
            ]);
        $this->formType->configureOptions($resolver);
    }

    /**
     * @param bool $stripTags
     *
     * @dataProvider submitDataProvider
     */
    public function testSubmit($stripTags)
    {
        $data = 'test';
        $this->htmlTagHelper
            ->expects($this->exactly($stripTags ? 1 : 0))
            ->method('stripTags')
            ->with($data)
            ->willReturn($data);
        $form = $this->factory->create($this->formType, null, ['strip_tags' => $stripTags]);
        $form->submit($data);
        $this->assertEquals($form->getData(), $data);
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'stripTags' => true
            ],
            [
                'stripTags' => false
            ],
        ];
    }
}
