<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\Select2Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

use Oro\Bundle\EmailBundle\Form\Type\EmailAddressFromType;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;

class EmailAddressFromTypeTest extends TypeTestCase
{
    protected $tokenAccessor;
    protected $relatedEmailsProvider;
    protected $mailboxManager;

    public function setUp()
    {
        parent::setUp();

        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $this->relatedEmailsProvider = $this->getMockBuilder('Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mailboxManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager')
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
        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->will($this->returnValue($relatedEmails));

        $this->mailboxManager->expects($this->once())
            ->method('findAvailableMailboxEmails')
            ->will($this->returnValue([]));

        $type = new EmailAddressFromType($this->tokenAccessor, $this->relatedEmailsProvider, $this->mailboxManager);
        $form = $this->factory->create($type);

        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }

    protected function getExtensions()
    {
        $select2Choice = new Select2Type(
            'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
            'oro_select2_choice'
        );

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
