<?php

namespace Oro\Bundle\NotificationBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\NotificationBundle\Entity\RecipientList;
use Oro\Bundle\UserBundle\Entity\Group;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;

class LoadRecipientListData extends AbstractFixture implements DependentFixtureInterface
{
    public const CUSTOM_EMAIL = 'custom.email@mail.com';
    public const RECIPIENT_LIST_WITH_USERS = 'RECIPIENT_LIST_WITH_USERS';
    public const RECIPIENT_LIST_WITH_GROUPS = 'RECIPIENT_LIST_WITH_GROUPS';
    public const RECIPIENT_LIST_WITH_GROUPS_AND_USERS = 'RECIPIENT_LIST_WITH_GROUPS_AND_USERS';
    public const RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_EMAIL = 'RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_EMAIL';
    public const RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_DUPLICATED_EMAIL =
        'RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_DUPLICATED_EMAIL';

    /**
     * @var ObjectManager
     */
    private $manager;

    #[\Override]
    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;

        $group = $this->createGroup();
        $this->createRecipientListWithUsers();
        $this->createRecipientListWithGroups($group);
        $this->createRecipientListWithGroupsAndUsers($group);
        $this->createRecipientListWithGroupsAndUsersAndEmail($group);
        $this->createRecipientListWithGroupsAndUsersAndDuplicatedEmail($group);
        $this->manager->flush();
    }

    private function createRecipientListWithUsers(): void
    {
        $recipientList = new RecipientList();
        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER));
        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER_2));

        $this->manager->persist($recipientList);
        $this->setReference(self::RECIPIENT_LIST_WITH_USERS, $recipientList);
    }

    private function createRecipientListWithGroups(Group $group): void
    {
        $recipientList = new RecipientList();
        $recipientList->addGroup($group);

        $this->manager->persist($recipientList);
        $this->setReference(self::RECIPIENT_LIST_WITH_GROUPS, $recipientList);
    }

    private function createRecipientListWithGroupsAndUsers(Group $group): void
    {
        $recipientList = new RecipientList();
        $recipientList->addGroup($group);

        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER));
        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER_2));

        $this->manager->persist($recipientList);
        $this->setReference(self::RECIPIENT_LIST_WITH_GROUPS_AND_USERS, $recipientList);
    }

    private function createRecipientListWithGroupsAndUsersAndEmail(Group $group): void
    {
        $recipientList = new RecipientList();
        $recipientList->addGroup($group);

        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER));
        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER_2));
        $recipientList->setEmail(self::CUSTOM_EMAIL);

        $this->manager->persist($recipientList);
        $this->setReference(self::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_EMAIL, $recipientList);
    }

    private function createRecipientListWithGroupsAndUsersAndDuplicatedEmail(Group $group): void
    {
        $recipientList = new RecipientList();
        $recipientList->addGroup($group);

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);

        $recipientList->addUser($simpleUser);
        $recipientList->addUser($this->getReference(LoadUserData::SIMPLE_USER_2));

        $recipientList->setEmail($simpleUser->getEmail());

        $this->manager->persist($recipientList);
        $this->setReference(self::RECIPIENT_LIST_WITH_GROUPS_AND_USERS_AND_DUPLICATED_EMAIL, $recipientList);
    }

    private function createGroup(): Group
    {
        $group = new Group();
        $group->setName('Recipient List Group');

        /** @var User $simpleUser */
        $simpleUser = $this->getReference(LoadUserData::SIMPLE_USER);
        $simpleUser->addGroup($group);

        /** @var User $userWithToken */
        $userWithToken = $this->getReference(LoadUserData::USER_WITH_CONFIRMATION_TOKEN);
        $userWithToken->addGroup($group);
        $this->manager->persist($group);

        return $group;
    }

    #[\Override]
    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
