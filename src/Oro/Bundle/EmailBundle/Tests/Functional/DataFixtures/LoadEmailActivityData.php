<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailThread;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailActivityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getDependencies(): array
    {
        return [LoadUserData::class, LoadOrganization::class];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager): void
    {
        $emailEntityBuilder = $this->getEmailEntityBuilder();
        $emailOriginHelper = $this->getEmailOriginHelper();
        $userManager = $this->getUserManager();

        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        $user1 = $this->createUser($userManager, $organization, 'Richard', 'Bradley');
        $user2 = $this->createUser($userManager, $organization, 'Brenda', 'Brock');
        $user3 = $this->createUser($userManager, $organization, 'Shawn', 'Bryson');
        $user4 = $this->createUser($userManager, $organization, 'Theodore', 'Knight');
        $user5 = $this->createUser($userManager, $organization, 'Walter', 'Werner');

        $this->setReference('user_1', $user1);
        $this->setReference('user_2', $user2);
        $this->setReference('user_3', $user3);
        $this->setReference('user_4', $user4);
        $this->setReference('user_5', $user5);

        $email1 = $this->createEmail(
            $emailEntityBuilder,
            $emailOriginHelper,
            $organization,
            'Test Email 1',
            'email1@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com'
        );
        $email1->addActivityTarget($user1);
        $email1->addActivityTarget($user2);
        $email1->addActivityTarget($user3);

        $email1_1 = $this->createEmail(
            $emailEntityBuilder,
            $emailOriginHelper,
            $organization,
            'Re: Test Email 1',
            'email1_1@orocrm-pro.func-test',
            'test2@example.com',
            'test1@example.com'
        );
        $email1_1->addActivityTarget($user1);
        $email1_1->addActivityTarget($user2);
        $email1_1->addActivityTarget($user5);

        $email2 = $this->createEmail(
            $emailEntityBuilder,
            $emailOriginHelper,
            $organization,
            'Test Email 1',
            'email2@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
            'test4@example.com'
        );
        $email2->addActivityTarget($user1);
        $email2->addActivityTarget($user4);

        $thread1 = new EmailThread();
        $email1->setThread($thread1);
        $email1_1->setThread($thread1);
        $manager->persist($thread1);

        $emailEntityBuilder->getBatch()->persist($manager);
        $manager->flush();

        $this->setReference('email_1', $email1);
        $this->setReference('email_1_1', $email1_1);
        $this->setReference('email_2', $email2);
    }

    private function createEmail(
        EmailEntityBuilder $emailEntityBuilder,
        EmailOriginHelper $emailOriginHelper,
        Organization $organization,
        string $subject,
        string $messageId,
        string $from,
        string $to,
        ?string $cc = null,
        ?string $bcc = null
    ): Email {
        $origin = $emailOriginHelper->getEmailOrigin($this->getReference('simple_user')->getEmail());
        $folder = $origin->getFolder(FolderType::SENT);
        $date = new \DateTime('now', new \DateTimeZone('UTC'));

        $emailUser = $emailEntityBuilder->emailUser(
            $subject,
            $from,
            $to,
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            $cc,
            $bcc,
            null,
            $organization
        );
        $emailUser->addFolder($folder);
        $emailUser->getEmail()->setMessageId($messageId);
        $emailUser->setOrigin($origin);

        return $emailUser->getEmail();
    }

    private function createUser(
        UserManager $userManager,
        Organization $organization,
        string $firstName,
        string $lastName
    ): User {
        /** @var User $user */
        $user = $userManager->createUser();
        $user->setOrganization($organization);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername(strtolower($firstName . '.' . $lastName));
        $user->setPassword(strtolower($firstName . '.' . $lastName));
        $user->setEmail(strtolower($firstName . '_' . $lastName . '@example.com'));

        $userManager->updateUser($user);

        return $user;
    }

    private function getEmailEntityBuilder(): EmailEntityBuilder
    {
        return $this->container->get('oro_email.email.entity.builder');
    }

    private function getEmailOriginHelper(): EmailOriginHelper
    {
        return $this->container->get('oro_email.tools.email_origin_helper');
    }

    private function getUserManager(): UserManager
    {
        return $this->container->get('oro_user.manager');
    }
}
