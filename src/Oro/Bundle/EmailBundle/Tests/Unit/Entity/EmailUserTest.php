<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity;

use DateTime;

use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tests\Unit\Fixtures\Entity\EmailAddress;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EmailUserTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterSetter()
    {
        $emailUser = new EmailUser();
        $email = new Email();
        $owner = new User();
        $organization = new Organization();
        $folder = new EmailFolder();
        $receivedAt = new DateTime('now');

        $emailUser->setEmail($email);
        $emailUser->setOrganization($organization);
        $emailUser->addFolder($folder);
        $emailUser->setSeen(true);
        $emailUser->setOwner($owner);
        $emailUser->setReceivedAt($receivedAt);

        $this->assertEquals($email, $emailUser->getEmail());
        $this->assertEquals($organization, $emailUser->getOrganization());
        $this->assertEquals($folder, $emailUser->getFolders()->first());
        $this->assertEquals(true, $emailUser->isSeen());
        $this->assertEquals($owner, $emailUser->getOwner());
        $this->assertEquals($receivedAt, $emailUser->getReceivedAt());
        $this->assertNull($emailUser->getCreatedAt());
    }

    public function testBeforeSave()
    {
        $emailUser = new EmailUser();
        $emailUser->beforeSave();

        $this->assertInstanceOf('\DateTime', $emailUser->getCreatedAt());
    }

    /**
     * @dataProvider outgoingEmailUserProvider
     */
    public function testIsOutgoing(EmailUser $emailUser)
    {
        $this->assertTrue($emailUser->isOutgoing());
        $this->assertFalse($emailUser->isIncoming());
    }

    public function outgoingEmailUserProvider()
    {
        $user = new User();

        return [
            'sent folder' => [
                (new EmailUser())
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::SENT)
                    )
            ],
            'drafts folder' => [
                (new EmailUser())
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::DRAFTS)
                    )
            ],
            'owner is sender' => [
                (new EmailUser())
                    ->setOwner($user)
                    ->setEmail(
                        (new Email())
                            ->setFromEmailAddress(
                                (new EmailAddress())
                                    ->setOwner($user)
                            )
                    )
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::OTHER)
                    )
            ],
        ];
    }

    /**
     * @dataProvider incomingEmailUserProvider
     */
    public function testIsIncoming(EmailUser $emailUser)
    {
        $this->assertTrue($emailUser->isIncoming());
        $this->assertFalse($emailUser->isOutgoing());
    }

    public function incomingEmailUserProvider()
    {
        $user = new User();
        $user2 = new User();

        return [
            'inbox folder' => [
                (new EmailUser())
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::INBOX)
                    )
            ],
            'spam folder' => [
                (new EmailUser())
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::SPAM)
                    )
            ],
            'owner is not sender' => [
                (new EmailUser())
                    ->setOwner($user)
                    ->setEmail(
                        (new Email())
                            ->setFromEmailAddress(
                                (new EmailAddress())
                                    ->setOwner($user2)
                            )
                    )
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::OTHER)
                    )
            ],
        ];
    }

    /**
     * @dataProvider incomingAndOutgoingProvider
     */
    public function testIsIncomingAndOutgoing(EmailUser $emailUser)
    {
        $this->assertTrue($emailUser->isIncoming());
        $this->assertTrue($emailUser->isOutgoing());
    }

    public function incomingAndOutgoingProvider()
    {
        return [
            'inbox and sent folders' => [
                (new EmailUser())
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::INBOX)
                    )
                    ->addFolder(
                        (new EmailFolder())
                            ->setType(FolderType::SENT)
                    )
            ],
        ];
    }
}
