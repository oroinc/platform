<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Validator\Constraints\MailboxOrigin;
use Oro\Bundle\EmailBundle\Validator\MailboxOriginValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MailboxOriginValidatorTest extends ConstraintValidatorTestCase
{
    /** @var Translator|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(Translator::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new MailboxOriginValidator($this->translator);
    }

    /**
     * Test for case: $value has folder with type Sent
     */
    public function testValueWithFolderSentOnRootLevel()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $constraint = new MailboxOrigin();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value is not EmailOrigin
     */
    public function testValueIsNotEmailOrigin()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $value = new EmailFolder();

        $constraint = new MailboxOrigin();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value has folder with type "Inbox" which has folder with type "Sent"
     */
    public function testValueWithFolderSentInFolderInbox()
    {
        $this->translator->expects($this->never())
            ->method('trans');

        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');
        $folderInbox->addSubFolder($folderSent);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new MailboxOrigin();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value is EmailOrigin but does not have folder with type "Sent"
     */
    public function testValueWithoutFolderSent()
    {
        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new MailboxOrigin();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%button%', null)
            ->assertRaised();
    }
}
