<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressRecipientsType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Test\TypeTestCase;

class EmailAddressRecipientsTypeTest extends TypeTestCase
{
    public function testFormShouldBeSubmittendAndViewShouldContainsRouteParameters()
    {
        $email = new Email();
        $email->setEntityClass('entityClass_param');
        $email->setEntityId('entityId_param');

        $form = $this->factory->createBuilder(FormType::class, $email)
            ->add('to', EmailAddressRecipientsType::class)
            ->getForm();

        $form->submit([]);

        $expectedRouteParameters = [
            'entityClass' => 'entityClass_param',
            'entityId'    => 'entityId_param',
        ];

        $view = $form->createView();
        $configs = $view->children['to']->vars['configs'];

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertArrayHasKey('route_parameters', $configs);
        $this->assertEquals($configs['route_parameters'], $expectedRouteParameters);
    }

    protected function getExtensions()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $emailAddressRecipients = new EmailAddressRecipientsType($configManager);

        return [
            new PreloadedExtension(
                [$emailAddressRecipients],
                []
            ),
        ];
    }
}
