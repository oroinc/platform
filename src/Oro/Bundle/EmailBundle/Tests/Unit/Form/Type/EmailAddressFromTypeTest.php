<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\EmailBundle\Form\Type\EmailAddressFromType;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class EmailAddressFromTypeTest extends TypeTestCase
{
    private $tokenAccessor;
    private $relatedEmailsProvider;
    private $mailboxManager;

    protected function setUp(): void
    {
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->relatedEmailsProvider = $this->createMock(RelatedEmailsProvider::class);
        $this->mailboxManager = $this->createMock(MailboxManager::class);

        parent::setUp();
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
            ->willReturn($user);

        $this->relatedEmailsProvider->expects($this->once())
            ->method('getEmails')
            ->willReturn($relatedEmails);

        $this->mailboxManager->expects($this->once())
            ->method('findAvailableMailboxEmails')
            ->willReturn([]);

        $form = $this->factory->create(EmailAddressFromType::class);

        $form->submit($formData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                 new EmailAddressFromType($this->tokenAccessor, $this->relatedEmailsProvider, $this->mailboxManager)
            ], [])
        ];
    }
}
