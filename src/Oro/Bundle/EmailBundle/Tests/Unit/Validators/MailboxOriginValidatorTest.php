<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Validator\Constraints\MailboxOrigin;
use Oro\Bundle\EmailBundle\Validator\MailboxOriginValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MailboxOriginValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var MailboxOrigin */
    protected $constraint;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var MailboxOriginValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new MailboxOrigin();

        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new MailboxOriginValidator(
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    /**
     * Test for case: $value has folder with type Sent
     */
    public function testValueWithFolderSentOnRootLevel()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->translator->expects($this->never())
            ->method('trans');

        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * Test for case: $value is not EmailOrigin
     */
    public function testValueIsNotEmailOrigin()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->translator->expects($this->never())
            ->method('trans');

        $value = new EmailFolder();

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * Test for case: $value has folder with type "Inbox" which has folder with type "Sent"
     */
    public function testValueWithFolderSentInFolderInbox()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->translator->expects($this->never())
            ->method('trans');

        $folderSent = new EmailFolder();
        $folderSent->setType('sent');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');
        $folderInbox->addSubFolder($folderSent);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * Test for case: $value is EmailOrigin but does not have folder with type "Sent"
     */
    public function testValueWithoutFolderSent()
    {
        $this->context->expects($this->once())
            ->method('addViolation');
        $this->translator->expects($this->once())
            ->method('trans')
            ->with('oro.imap.configuration.connect_and_retrieve_folders')
            ->will($this->returnArgument(0));

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }
}
