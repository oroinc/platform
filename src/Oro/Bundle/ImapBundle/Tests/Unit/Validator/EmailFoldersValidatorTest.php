<?php

namespace Oro\Bundle\ImapBundle\Tests\Unit\Validator;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\Translation\Translator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\ImapBundle\Validator\Constraints\EmailFolders;
use Oro\Bundle\ImapBundle\Validator\EmailFoldersValidator;
use Oro\Bundle\ImapBundle\Entity\UserEmailOrigin;

class EmailFoldersValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailFolders */
    protected $constraint;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var Translator|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var EmailFoldersValidator */
    protected $validator;

    protected function setUp()
    {
        $this->constraint = new EmailFolders();

        $this->context = $this
            ->createMock('Symfony\Component\Validator\ExecutionContextInterface');

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new EmailFoldersValidator(
            $this->translator
        );
        $this->validator->initialize($this->context);
    }

    /**
     * Test for case: $value has folder
     */
    public function testValueWithFolderOnRootLevel()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->translator->expects($this->never())
            ->method('trans');

        $folderSent = new EmailFolder();
        $folderSent->setType('index');

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
     * Test for case: $value has folder
     */
    public function testValueWithFolders()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->translator->expects($this->never())
            ->method('trans');

        $folderInbox = new EmailFolder();
        $folderInbox->setType('inbox');

        $value = new UserEmailOrigin();
        $value->addFolder($folderInbox);

        $this->validator->validate($value, $this->constraint);
    }

    /**
     * Test for case: $value is EmailOrigin but does not have any folders
     */
    public function testValueWithoutFolders()
    {
        $this->context->expects($this->once())
            ->method('addViolation')
        ->with('oro.imap.validator.configuration.folders_are_not_selected');

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $value = new PersistentCollection(
            $em,
            'Oro\Bundle\ImapBundle\Tests\Unit\Stub\TestEmailFolder',
            new ArrayCollection([])
        );

        $this->validator->validate($value, $this->constraint);
    }
}
