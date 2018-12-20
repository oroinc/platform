<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailAddressType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailAddressTypeTest extends TypeTestCase
{
    public function testSubmitValidDataForSingleAddressForm()
    {
        $formData = ' John Smith <john@example.com> ';

        $form = $this->factory->create(EmailAddressType::class);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var string $result */
        $result = $form->getData();
        $this->assertEquals('John Smith <john@example.com>', $result);

        $view = $form->createView();
        $this->assertEquals(trim($formData), $view->vars['value']);
    }

    public function testSubmitValidDataForMultipleAddressForm()
    {
        $formData = ' John Smith 1 <john1@example.com> ;; ; "John Smith 2" <john2@example.com>; john3@example.com';

        $form = $this->factory->create(EmailAddressType::class, null, array('multiple' => true));

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        /** @var array $result */
        $result = $form->getData();
        $this->assertEquals(
            array('John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'),
            $result
        );

        $view = $form->createView();
        $this->assertEquals(trim($formData), $view->vars['value']);
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'multiple' => false
                )
            );

        $type = new EmailAddressType(array());
        $type->configureOptions($resolver);
    }

    public function testGetParent()
    {
        $type = new EmailAddressType(array());
        $this->assertEquals(TextType::class, $type->getParent());
    }
}
