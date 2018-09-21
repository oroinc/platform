<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\NotificationBundle\Entity\Repository\RecipientListRepository;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures\LoadRecipientListData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class RecipientListRepositoryTest extends WebTestCase
{
    /**
     * @var RecipientListRepository
     */
    protected $repository;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadRecipientListData::class]);
        $this->repository = self::getContainer()
            ->get('doctrine')
            ->getRepository('OroNotificationBundle:RecipientList');
    }

    public function testGetRecipientEmailsForRecipientListWithUsers(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_USERS);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUserPassword */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);

        $expectedEmails = [
            $simpleUser->getEmail(),
            $simpleUser2->getEmail()
        ];

        $this->assertEmails($expectedEmails, $this->repository->getRecipientEmails($recipientList));
    }

    public function testGetRecipientsForRecipientListWithUsers(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_USERS);

        $expectedRecipients = [
            $this->getReference(LoadUserData::SIMPLE_USER),
            $this->getReference(LoadUserData::SIMPLE_USER_2)
        ];

        $this->assertRecipients($expectedRecipients, $this->repository->getRecipients($recipientList));
    }

    public function testGetRecipientEmailsForRecipientListWithGroups(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser->getEmail(),
            $userWithToken->getEmail()
        ];

        $this->assertEmails($expectedEmails, $this->repository->getRecipientEmails($recipientList));
    }

    public function testGetRecipientsForRecipientListWithGroups(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedRecipients = [
            new EmailAddressWithContext($simpleUser->getEmail(), $simpleUser),
            new EmailAddressWithContext($userWithToken->getEmail(), $userWithToken)
        ];

        $this->assertRecipients($expectedRecipients, $this->repository->getRecipients($recipientList));
    }

    public function testGetRecipientEmailsForRecipientListWithGroupsAndUsers(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser->getEmail(),
            $simpleUser2->getEmail(),
            $userWithToken->getEmail()
        ];

        $this->assertEmails($expectedEmails, array_values($this->repository->getRecipientEmails($recipientList)));
    }

    public function testGetRecipientsForRecipientListWithGroupsAndUsers(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedRecipients = [
            $simpleUser,
            $simpleUser2,
            new EmailAddressWithContext($userWithToken->getEmail(), $userWithToken)
        ];

        $this->assertRecipients($expectedRecipients, $this->repository->getRecipients($recipientList));
    }

    public function testGetRecipientEmailsForRecipientListWithGroupsAndUsersAndEmail(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_EMAIL);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser->getEmail(),
            $simpleUser2->getEmail(),
            $userWithToken->getEmail(),
            LoadRecipientListData::CUSTOM_EMAIL
        ];

        $this->assertEmails($expectedEmails, $this->repository->getRecipientEmails($recipientList));
    }

    public function testGetRecipientsForRecipientListWithGroupsAndUsersAndEmail(): void
    {
        $recipientList = $this->getReference(LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_EMAIL);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser,
            $simpleUser2,
            new EmailAddressWithContext($userWithToken->getEmail(), $userWithToken),
            new EmailAddressWithContext(LoadRecipientListData::CUSTOM_EMAIL)
        ];

        $this->assertRecipients($expectedEmails, array_values($this->repository->getRecipients($recipientList)));
    }

    public function testGetRecipientEmailsForRecipientListWithGroupsAndUsersAndDuplicatedEmail(): void
    {
        $recipientList = $this->getReference(
            LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_DUPLICATED_EMAIL
        );

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser->getEmail(),
            $simpleUser2->getEmail(),
            $userWithToken->getEmail()
        ];

        $this->assertEmails($expectedEmails, array_values($this->repository->getRecipientEmails($recipientList)));
    }

    public function testGetRecipientsForRecipientListWithGroupsAndUsersAndDuplicatedEmail(): void
    {
        $recipientList = $this->getReference(
            LoadRecipientListData::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_DUPLICATED_EMAIL
        );

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        /** @var User $simpleUser */
        $simpleUser2 = $this->getReference(LoadUserData::SIMPLE_USER_2);
        /** @var User $simpleUserPassword */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);

        $expectedEmails = [
            $simpleUser,
            $simpleUser2,
            new EmailAddressWithContext($userWithToken->getEmail(), $userWithToken),
        ];

        $this->assertRecipients($expectedEmails, array_values($this->repository->getRecipients($recipientList)));
    }

    /**
     * @param array $expectedRecipients
     * @param array $actualRecipients
     */
    private function assertRecipients(array $expectedRecipients, array $actualRecipients): void
    {
        self::assertCount(\count($expectedRecipients), $actualRecipients);
        foreach ($expectedRecipients as $recipient) {
            self::assertContains($recipient, $actualRecipients, '', false, false);
        }
    }

    /**
     * @param array $expectedEmails
     * @param array $actualEmails
     */
    private function assertEmails(array $expectedEmails, array $actualEmails): void
    {
        self::assertCount(\count($expectedEmails), $actualEmails);
        foreach ($expectedEmails as $email) {
            self::assertContains($email, $actualEmails);
        }
    }
}
