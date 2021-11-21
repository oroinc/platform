<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFoldersValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class EmailFoldersValidatorTest extends ConstraintValidatorTestCase
{
    protected function createValidator()
    {
        return new EmailFoldersValidator();
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate($this->createMock(UserEmailOrigin::class), $this->createMock(Constraint::class));
    }

    public function testUserEmailOriginValueWithFolderOnRootLevel()
    {
        $folderSent = new EmailFolder();
        $folderSent->setType('index')->setSyncEnabled(true);

        $value = new UserEmailOrigin();
        $value->addFolder($folderSent);

        $constraint = new EmailFolders();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValueIsNotEmailOrigin()
    {
        $constraint = new EmailFolders();
        $this->validator->validate(new \stdClass(), $constraint);

        $this->assertNoViolation();
    }

    public function testValueWithFolders()
    {
        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->setSyncEnabled(true);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new EmailFolders();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValueWithSubFolders()
    {
        $subFolderInbox = new EmailFolder();
        $subFolderInbox->setType('subfolder')->setSyncEnabled(true);

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->addSubFolder($subFolderInbox);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new EmailFolders();
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValueWithSubFoldersNotSyncEnabled()
    {
        $subFolderInbox = new EmailFolder();
        $subFolderInbox->setType('subfolder');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->addSubFolder($subFolderInbox);

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $constraint = new EmailFolders();
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testEmptyCollectionValueWithViolation()
    {
        $constraint = new EmailFolders();
        $this->validator->validate(new ArrayCollection([]), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testUnsupportedCollectionValueWithViolation()
    {
        $constraint = new EmailFolders();
        $this->validator->validate(new ArrayCollection([new \stdClass()]), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testNotEmptyCollectionValueWithViolation()
    {
        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $constraint = new EmailFolders();
        $this->validator->validate(new ArrayCollection([$folderInbox]), $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testNotEmptyCollectionValue()
    {
        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox')->setSyncEnabled(true);

        $constraint = new EmailFolders();
        $this->validator->validate(new ArrayCollection([$folderInbox]), $constraint);

        $this->assertNoViolation();
    }
}
