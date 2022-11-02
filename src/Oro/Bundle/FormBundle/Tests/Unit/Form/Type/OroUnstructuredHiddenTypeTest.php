<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroUnstructuredHiddenType;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OroUnstructuredHiddenTypeTest extends FormIntegrationTestCase
{
    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->exactly(2))
            ->method('setDefaults')
            ->withConsecutive(
                [$this->isType('array')],
                [['multiple' => true]]
            );

        $formType = new OroUnstructuredHiddenType();
        $formType->configureOptions($resolver);
    }

    public function testSubmit()
    {
        $formData = [
            'type' => 1,
            ['value' => ['val0', 'val1']]
        ];
        $form = $this->factory->create(OroUnstructuredHiddenType::class);
        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }
}
