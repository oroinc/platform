<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType;
use Oro\Bundle\EmailBundle\Form\Type\EmailType;
use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressType;
use Oro\Bundle\EmailBundle\Form\Type\EmailTemplateSelectType;

class EmailTypeTest extends TypeTestCase
{
    protected function getExtensions()
    {
        $emailAddressType  = new EmailAddressType();
        $translatableType = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Form\Type\TranslatableEntityType')
            ->disableOriginalConstructor()
            ->getMock();
        $translatableType->expects($this->any())
            ->method('getName')
            ->will($this->returnValue(TranslatableEntityType::NAME));

        $select2ChoiceType = new Select2Type(TranslatableEntityType::NAME);
        $emailTemplateList = new EmailTemplateSelectType();

        return array(
            new PreloadedExtension(
                [
                    TranslatableEntityType::NAME  => $translatableType,
                    $select2ChoiceType->getName() => $select2ChoiceType,
                    $emailTemplateList->getName() => $emailTemplateList,
                    $emailAddressType->getName()  => $emailAddressType,
                ],
                []
            )
        );
    }

    public function testSubmitValidData()
    {
        $formData = [
            'gridName' => 'test_grid',
            'from'     => 'John Smith <john@example.com>',
            'to'       => 'John Smith 1 <john1@example.com>; "John Smith 2" <john2@example.com>; john3@example.com',
            'subject'  => 'Test subject',
            'body'     => 'Test body',
            'template' => new EmailTemplate(),
        ];

        $type = new EmailType();
        $form = $this->factory->create($type);

        $form->submit($formData);
        $this->assertTrue($form->isSynchronized());

        /** @var Email $result */
        $result = $form->getData();
        $this->assertInstanceOf('Oro\Bundle\EmailBundle\Form\Model\Email', $result);
        $this->assertEquals('test_grid', $result->getGridName());
        $this->assertEquals('John Smith <john@example.com>', $result->getFrom());
        $this->assertEquals(
            ['John Smith 1 <john1@example.com>', '"John Smith 2" <john2@example.com>', 'john3@example.com'],
            $result->getTo()
        );
        $this->assertEquals('Test subject', $result->getSubject());
        $this->assertEquals('Test body', $result->getBody());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class'         => 'Oro\Bundle\EmailBundle\Form\Model\Email',
                    'intention'          => 'email',
                    'csrf_protection'    => true,
                    'cascade_validation' => true
                ]
            );

        $type = new EmailType([]);
        $type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $type = new EmailType([]);
        $this->assertEquals('oro_email_email', $type->getName());
    }
}
