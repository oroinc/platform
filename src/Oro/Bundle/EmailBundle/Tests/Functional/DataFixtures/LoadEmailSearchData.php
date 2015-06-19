<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EmailBundle\Builder\EmailEntityBuilder;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class LoadEmailSearchData extends AbstractFixture implements ContainerAwareInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var Organization */
    protected $organization;

    /** @var EmailEntityBuilder */
    protected $emailEntityBuilder;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->emailEntityBuilder = $container->get('oro_email.email.entity.builder');
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em           = $manager;
        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $this->createUser('Richard', 'Bradley', 'test1@example.com');
        $this->createUser('Brenda', 'Brock', 'test2@example.com');
        $this->createUser('Shawn', 'Bryson', 'test3@example.com');

        $this->em->flush();
    }


    /**
     * @param string $firstName
     * @param string $lastName
     * @param string $email
     *
     * @return User
     */
    protected function createUser($firstName, $lastName, $email)
    {
        $user = new User();
        $user->setOrganization($this->organization);
        $user->setFirstName($firstName);
        $user->setLastName($lastName);
        $user->setUsername(strtolower($firstName . '.' . $lastName));
        $user->setPassword(strtolower($firstName . '.' . $lastName));
        $user->setEmail($email);

        $this->em->persist($user);
    }
}
