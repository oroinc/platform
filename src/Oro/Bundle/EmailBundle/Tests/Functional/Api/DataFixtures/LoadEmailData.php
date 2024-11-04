<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\InternalEmailOrigin;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressVisibilityManager;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\Role;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\UserManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadEmailData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    public const ENCODED_ATTACHMENT_CONTENT
        = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJ'
        . 'AAAABHNCSVQICAgIfAhkiAAAAAtJREFUCJlj+A8EAAn7A/3jVfKcAAAAAElFTkSuQmCC';

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadBusinessUnit::class, LoadUser::class];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var EmailEntityBuilder $emailBuilder */
        $emailBuilder = $this->container->get('oro_email.email.entity.builder');

        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);

        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);
        $user1 = $this->loadUser($manager, 'user1', $organization);
        $user2 = $this->loadUser($manager, 'user2', $organization);

        $this->updateMainUserEmailOrigin($user);

        /** @var EmailAddressVisibilityManager $emailAddressVisibilityManager */
        $emailAddressVisibilityManager = $this->container->get('oro_email.email_address_visibility.manager');
        $emailAddressVisibilityManager->updateEmailAddressVisibility(
            'e1@example.com',
            $organization->getId(),
            false
        );
        $emailAddressVisibilityManager->updateEmailAddressVisibility(
            'e1@example.com',
            $organization->getId(),
            false
        );

        $email1 = $this->loadEmail(
            1,
            $emailBuilder,
            'Test First Email',
            $user->getEmail(),
            $user1->getEmail(),
            false,
            [self::ENCODED_ATTACHMENT_CONTENT],
            $user
        );
        $email1->getEmail()->setRefs('<other@email-api.func-test>');
        $this->loadEmail(
            2,
            $emailBuilder,
            'Test Second Email',
            $user->getEmail(),
            $user1->getEmail(),
            null,
            [],
            $user
        );
        $email3 = $this->loadEmail(
            3,
            $emailBuilder,
            'Third Email',
            $user->getEmail(),
            'e1@example.com',
            true,
            [self::ENCODED_ATTACHMENT_CONTENT],
            $user
        );
        $email3->setSeen(true);
        $email3->getEmail()->setRefs('<id2@email-api.func-test> <test1@email-api.func-test>');
        $email3->getEmail()->getEmailBody()->setBodyContent('Third email body');
        $email3->getEmail()->getEmailBody()->setTextBody('Third email body');
        $email3->getEmail()->getEmailBody()->getAttachments()[0]->setEmbeddedContentId('1234567890');
        $this->loadEmail(
            4,
            $emailBuilder,
            'Test Fourth Email',
            $user1->getEmail(),
            $user->getEmail(),
            false,
            [self::ENCODED_ATTACHMENT_CONTENT],
            $user1
        );
        $this->loadEmail(
            5,
            $emailBuilder,
            'Test Fifth Email',
            'e1@example.com',
            'e2@example.com',
            false,
            [self::ENCODED_ATTACHMENT_CONTENT],
            $user1
        );
        $email6 = $this->loadEmail(
            6,
            $emailBuilder,
            'Test Sixth Email',
            $user->getEmail(),
            $user1->getEmail(),
            false,
            [],
            $user
        );
        $email6->getEmail()->setRefs('<other@email-api.func-test>');
        $email6->getEmail()->addActivityTarget($user2);

        $emailBuilder->getBatch()->persist($manager);
        $manager->flush();

        $email3User2 = new EmailUser();
        $email3User2->setEmail($email3->getEmail());
        $email3User2->setOrganization($email3->getOrganization());
        $email3User2->setOwner($user1);
        $email3User2->setReceivedAt(new \DateTime('2022-05-01 15:00:00.050'));
        $email3User2->addFolder($emailBuilder->folderInbox());
        $this->setReference('emailUser_3_2', $email3User2);
        $manager->persist($email3User2);
        $manager->flush();
    }

    private function loadEmail(
        int $number,
        EmailEntityBuilder $emailBuilder,
        string $subject,
        string $from,
        string $to,
        ?bool $bodyIsHtml,
        array $attachments,
        User $owner
    ): EmailUser {
        $emailUser = $emailBuilder->emailUser(
            $subject,
            $from,
            $to,
            new \DateTime('2022-05-01 12:01:00.050'),
            new \DateTime('2022-05-01 15:00:00.050'),
            new \DateTime('2022-05-01 12:05:00.050'),
            Email::NORMAL_IMPORTANCE,
            'cc' . $number . '@example.com',
            'bcc' . $number . '@example.com',
            $owner,
            $owner->getOrganization()
        );

        $origin = $this->getReference(
            ($owner !== $this->getReference(LoadUser::USER) ? $owner->getUserIdentifier() . '_' : '') . 'email_origin'
        );
        $emailUser->setOrigin($origin);
        $emailUser->addFolder($origin->getFolder(FolderType::SENT));
        $emailUser->getEmail()->addActivityTarget($owner);
        $emailUser->getEmail()->setHead(true);

        if (null !== $bodyIsHtml) {
            $bodyContent = $subject . ' body';
            if ($bodyIsHtml) {
                $bodyContent = sprintf('<p>%s</p>', $bodyContent);
            }
            $emailBody = $emailBuilder->body($bodyContent, $bodyIsHtml, true);
            $emailUser->getEmail()->setEmailBody($emailBody);
            $emailUser->getEmail()->setBodySynced(true);
            $this->setReference('emailBody_' . $number, $emailBody);

            foreach ($attachments as $i => $attachmentContent) {
                $attachment = $emailBuilder->attachment('test.png', 'image/png');
                $attachment->setContent($emailBuilder->attachmentContent($attachmentContent, 'base64'));
                $emailBody->addAttachment($attachment);
                $this->setReference('emailAttachment_' . $number . '_' . ($i + 1), $attachment);
            }
        }

        $emailUser->getEmail()->setMessageId(sprintf('<id%s@email-api.func-test>', $number));
        $this->setReference('email_' . $number, $emailUser->getEmail());
        $this->setReference('emailUser_' . $number, $emailUser);

        return $emailUser;
    }

    private function loadUser(ObjectManager $manager, string $username, Organization $organization): User
    {
        /** @var UserManager $userManager */
        $userManager = $this->container->get('oro_user.manager');

        /** @var User $user */
        $user = $userManager->createUser();
        $user->setUsername($username);
        $user->setOwner($this->getReference('business_unit'));
        $user->setPlainPassword($username . '_password');
        $user->setEmail($username . '@example.com');
        $user->setOrganization($organization);
        $user->addOrganization($organization);
        $user->setEnabled(true);
        $user->addUserRole($this->loadRole($manager, 'ROLE_ADMINISTRATOR'));
        $userManager->updateUser($user);
        $this->setReference($username, $user);

        $sentFolder = new EmailFolder();
        $sentFolder->setType(FolderType::SENT);
        $sentFolder->setName('Sent');
        $sentFolder->setFullName('Sent');
        $manager->persist($sentFolder);

        $origin = new InternalEmailOrigin();
        $origin->setName($username . '_email_origin');
        $origin->setActive(true);
        $origin->addFolder($sentFolder);
        $origin->setOwner($user);
        $origin->setOrganization($organization);
        $manager->persist($origin);
        $this->setReference($username . '_email_origin', $origin);

        $user->addEmailOrigin($origin);

        return $user;
    }

    private function loadRole(ObjectManager $manager, string $name): Role
    {
        if ($this->hasReference($name)) {
            return $this->getReference($name);
        }

        $role = $manager->getRepository(Role::class)->findOneBy(['role' => $name]);
        $this->setReference($name, $role);

        return $role;
    }

    private function updateMainUserEmailOrigin(User $user): void
    {
        /** @var EmailOriginHelper $emailOriginHelper */
        $emailOriginHelper = $this->container->get('oro_email.tools.email_origin_helper');
        $origin = $emailOriginHelper->getEmailOrigin($user->getEmail());
        $this->setReference('email_origin', $origin);
        foreach ($origin->getFolders() as $folder) {
            if (FolderType::OTHER !== $folder->getType()) {
                $folder->setName(ucfirst($folder->getName()));
                $folder->setFullName(ucfirst($folder->getFullName()));
            }
        }
    }
}
