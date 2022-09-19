<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Validator\Constraints\MailboxOrigin;
use Oro\Bundle\EmailBundle\Validator\Constraints\MailboxOriginValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class MailboxOriginValidatorTest extends ConstraintValidatorTestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        parent::setUp();
    }

    protected function createValidator(): MailboxOriginValidator
    {
        return new MailboxOriginValidator($this->translator);
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(UserEmailOrigin::class), $this->createMock(Constraint::class));
    }

    /**
     * Test for case: $value has folder with type Sent
     */
    public function testValueWithFolderSentOnRootLevel(): void
    {
        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, new MailboxOrigin());

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value is not EmailOrigin
     */
    public function testValueIsNotEmailOrigin(): void
    {
        $value = new EmailFolder();

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, new MailboxOrigin());

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value has folder with type "Inbox" which has folder with type "Sent"
     */
    public function testValueWithFolderSentInFolderInbox(): void
    {
        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');
        $folderInbox->addSubFolder($folderSent);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, new MailboxOrigin());

        $this->assertNoViolation();
    }

    /**
     * Test for case: $value is EmailOrigin but does not have folder with type "Sent"
     */
    public function testValueWithoutFolderSent(): void
    {
        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new MailboxOrigin();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('%button%', '')
            ->assertRaised();
    }

    public function testWhenSentFolderInOneOfSubFolders(): void
    {
        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $subFolder = new EmailFolder();
        $subFolder->addSubFolder($folderSent);

        $rootFolder = new EmailFolder();
        $rootFolder->addSubFolder($subFolder);

        $value = new UserEmailOrigin();
        $value->addFolder($rootFolder);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, new MailboxOrigin());

        $this->assertEmpty($this->context->getViolations());
    }
}
