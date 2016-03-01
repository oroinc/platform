<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Entity\Email;

class LoadEmailSuggestionData extends AbstractFixture implements DependentFixtureInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var Organization */
    protected $organization;

    /** @var Email */
    protected $email;
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return ['Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailActivityData'];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->em           = $manager;
        $this->organization = $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();

        $this->email = new Email();
        $this->email->setEmail('test1@example.com');

        $this->em->persist($this->email);

        $user4 = $this->createUser('Lucas', 'Thornton');
        $user5 = $this->createUser('Traci', 'Patric');

        $this->setReference('user_4', $user4);
        $this->setReference('user_5', $user5);

        $this->em->flush();
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

        $user->addEmail($this->email);

        $this->em->persist($user);

        return $user;
    }
}
