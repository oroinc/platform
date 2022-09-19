<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Validator\Constraints\MailboxOrigin;
use Oro\Bundle\EmailBundle\Validator\MailboxOriginValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MailboxOriginValidatorTest extends ConstraintValidatorTestCase
{
    /** @var MailboxOrigin */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    protected function createValidator()
    {
        return new MailboxOriginValidator(
            $this->translator
        );
    }

    protected function setUp(): void
    {
        $this->constraint = new MailboxOrigin();
        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * Test for case: $value has folder with type Sent
     */
    public function testValueWithFolderSentOnRootLevel()
    {
        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, $this->constraint);

        $this->assertEmpty($this->context->getViolations());
    }

    /**
     * Test for case: $value is not EmailOrigin
     */
    public function testValueIsNotEmailOrigin()
    {
        $value = new EmailFolder();

        $this->translator->expects($this->never())
            ->method('trans');

        $this->validator->validate($value, $this->constraint);

        $this->assertEmpty($this->context->getViolations());
    }

    /**
     * Test for case: $value has folder with type "Inbox" which has folder with type "Sent"
     */
    public function testValueWithFolderSentInFolderInbox()
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

        $this->validator->validate($value, $this->constraint);

        $this->assertEmpty($this->context->getViolations());
    }

    /**
     * Test for case: $value is EmailOrigin but does not have folder with type "Sent"
     */
    public function testValueWithoutFolderSent()
    {
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.imap.configuration.connect_and_retrieve_folders')
            ->willReturn('_tranlated_');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->atPath('property.path')
            ->setParameter('%button%', '_tranlated_')
            ->assertRaised();
    }

    public function testWhenSentFolderInOneOfSubFolders()
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

        $this->validator->validate($value, $this->constraint);

        $this->assertEmpty($this->context->getViolations());
    }
}
