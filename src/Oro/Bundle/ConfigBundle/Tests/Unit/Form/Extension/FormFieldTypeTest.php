<?php

namespace ConfigBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\FormBuilderInterface;

use Oro\Bundle\ConfigBundle\Form\Extension\FormFieldType;

class FormFieldTypeTest extends TypeTestCase
{
    /**
     * @dataProvider buildFormDataProvider
     *
     * @param bool $resettable   Is the form has resettable option
     * @param int $expectedCount Expected listeners invocation count
     */
    public function testBuildForm($resettable, $expectedCount)
    {
        $builder = $this->getFormBuilderMock($expectedCount);

        $this->getFormExtension()->buildForm(
            $builder,
            [
                'resettable' => $resettable,
                'target_field_type' => 'array',
                'target_field_options' => [],
            ]
        );
    }

    public function buildFormDataProvider()
    {
        return [
            'resettable' => [true, 1],
            'non-resettable' => [false, 0],
        ];
    }

    public function getExtendedType()
    {
        $this->assertEquals('oro_config_form_field_type', $this->getFormExtension()->getExtendedType());
    }

    /**
     * @param int $expectedCount Expected invocation count
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    private function getFormBuilderMock($expectedCount)
    {
        /* @var FormBuilderInterface|\PHPUnit_Framework_MockObject_MockObject $builder */
        $builder = $this->getMock(FormBuilderInterface::class);
        $fieldBuilder = $this->getMock(FormBuilderInterface::class);

        $fieldBuilder->expects($this->exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT);

        $builder->expects($this->exactly($expectedCount))
            ->method('get')
            ->with('use_parent_scope_value')
            ->willReturn($fieldBuilder);

        $builder->expects($this->exactly($expectedCount))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA);

        return $builder;
    }

    private function getFormExtension()
    {
        return new FormFieldType();
    }
}
