<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\EmailFoldersValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EmailFoldersValidatorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EmailFolders */
    protected $constraint;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /** @var EmailFoldersValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new EmailFolders();

        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new EmailFoldersValidator();
        $this->validator->initialize($this->context);
    }

    public function testUserEmailOriginValueWithFolderOnRootLevel()
    {
        $this->assertViolationNotAdded();

        $folderSent = new EmailFolder();
        $folderSent->setType('index')->setSyncEnabled(true);

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValueIsNotEmailOrigin()
    {
        $this->assertViolationNotAdded();

        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    public function testValueWithFolders()
    {
        $this->assertViolationNotAdded();

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->setSyncEnabled(true);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValueWithSubFolders()
    {
        $this->assertViolationNotAdded();

        $subFolderInbox = new EmailFolder();
        $subFolderInbox->setType('subfolder')->setSyncEnabled(true);

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->addSubFolder($subFolderInbox);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }

    public function testValueWithSubFoldersNotSyncEnabled()
    {
        $this->assertViolationAdded();

        $subFolderInbox = new EmailFolder();
        $subFolderInbox->setType('subfolder');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->addSubFolder($subFolderInbox);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }

    public function testEmptyCollectionValueWithViolation()
    {
        $this->assertViolationAdded();

        $this->validator->validate(new ArrayCollection([]), $this->constraint);
    }

    public function testUnsupportedCollectionValueWithViolation()
    {
        $this->assertViolationAdded();

        $this->validator->validate(new ArrayCollection([new \stdClass()]), $this->constraint);
    }

    public function testNotEmptyCollectionValueWithViolation()
    {
        $this->assertViolationAdded();

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $this->validator->validate(new ArrayCollection([$folderInbox]), $this->constraint);
    }

    public function testNotEmptyCollectionValue()
    {
        $this->assertViolationNotAdded();

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->setSyncEnabled(true);

        $this->validator->validate(new ArrayCollection([$folderInbox]), $this->constraint);
    }

    private function assertViolationAdded()
    {
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with('oro.imap.validator.configuration.folders_are_not_selected');
    }

    private function assertViolationNotAdded()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
    }
}
