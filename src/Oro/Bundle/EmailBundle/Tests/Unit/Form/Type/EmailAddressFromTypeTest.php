<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\EmailBundle\Form\Type\EmailAddressFromType;
use Oro\Bundle\UserBundle\Entity\User;

class EmailAddressFromTypeTest extends TypeTestCase
{
    protected $securityFacade;
    protected $relatedEmailsProvider;

    public function setUp()
    {
        parent::setUp();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->relatedEmailsProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testSubmitValidData()
    {
        $formData = 'Mail <email@example.com>';

        $relatedEmails = [
            'email@example.com'  => 'Mail <email@example.com>',
            'email2@example.com' => 'Mail2 <email2@example.com>',
        ];

        $user = new User();
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->will($this->returnValue($user));

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->will($this->returnValue($relatedEmails));

        $type = new EmailAddressFromType($this->securityFacade, $this->relatedEmailsProvider);
        $form = $this->factory->create($type);

        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }

    protected function getExtensions()
    {
        $select2Choice = new Select2Type('choice');

        return [
            new PreloadedExtension(
                [
                    $select2Choice->getName() => $select2Choice,
                ],
                []
            ),
        ];
    }
}
