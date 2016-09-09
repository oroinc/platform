<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Model\FolderType;
use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class LoadEmailActivityData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var Organization */
    protected $organization;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
        $this->emailOriginHelper = $container->get('oro_email.tools.email_origin_helper');
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadUserData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em           = $manager;
        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $user1 = $this->createUser('Richard', 'Bradley');
        $user2 = $this->createUser('Brenda', 'Brock');
        $user3 = $this->createUser('Shawn', 'Bryson');

        $this->setReference('user_1', $user1);
        $this->setReference('user_2', $user2);
        $this->setReference('user_3', $user3);

        $email1 = $this->createEmail(
            'Test Email 1',
            'email1@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com'
        );
        $email1->addActivityTarget($user1);
        $email1->addActivityTarget($user2);
        $email1->addActivityTarget($user3);

        $email2 = $this->createEmail(
            'Test Email 1',
            'email2@orocrm-pro.func-test',
            'test1@example.com',
            'test2@example.com',
            'test3@example.com',
            'test4@example.com'
        );
        $email2->addActivityTarget($user1);

        $this->emailEntityBuilder->getBatch()->persist($this->em);
        $this->em->flush();

        $this->setReference('email_1', $email1);
        $this->setReference('email_2', $email2);
    }

    /**
     * @param string               $subject
     * @param string               $messageId
     * @param string               $from
     * @param string|string[]      $to
     * @param string|string[]|null $cc
     * @param string|string[]|null $bcc
     *
     * @return Email
     */
    protected function createEmail($subject, $messageId, $from, $to, $cc = null, $bcc = null)
    {
        $origin = $this->emailOriginHelper->getEmailOrigin($this->getReference('simple_user')->getEmail());
        $folder = $origin->getFolder(FolderType::SENT);
        $date   = new \DateTime('now', new \DateTimeZone('UTC'));

        $emailUser = $this->emailEntityBuilder->emailUser(
            $subject,
            $from,
            $to,
            $date,
            $date,
            $date,
            Email::NORMAL_IMPORTANCE,
            $cc,
            $bcc
        );
        $emailUser->addFolder($folder);
        $emailUser->getEmail()->setMessageId($messageId);
        $emailUser->setOrigin($origin);

        return $emailUser->getEmail();
    }

    /**
     * @param string $firstName
     * @param string $lastName
     *
     * @return User
     */
    protected function createUser($firstName, $lastName)
    {
        $user = new User();
        $user->setOrganization($this->organization);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername(strtolower($firstName . '.' . $lastName));
        $user->setPassword(strtolower($firstName . '.' . $lastName));
        $user->setEmail(strtolower($firstName . '_' . $lastName . '@example.com'));

        $this->em->persist($user);

        return $user;
    }
}
