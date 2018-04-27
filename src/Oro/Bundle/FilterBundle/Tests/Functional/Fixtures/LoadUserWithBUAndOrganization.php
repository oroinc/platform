<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class LoadUserWithBUAndOrganization extends AbstractFixture
{
    /** @var array */
    protected static $users = [
        [
            'username' => 'u1',
            'email' => 'u1@example.com',
            'password' => 'u1',
            'additional_email' => 'test1@example.com',
            'business_unit' => true,
            'organization' => false
        ],
        [
            'username' => 'u2',
            'email' => 'u2@example.com',
            'password' => 'u2',
            'additional_email' => 'test2@example.com',
            'business_unit' => true,
            'organization' => false
        ],
        [
            'username' => 'u3',
            'email' => 'u3@example.com',
            'password' => 'u3',
            'additional_email' => 'test3@example.com',
            'business_unit' => false,
            'organization' => true
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getEntity($manager, BusinessUnit::class, 'mainBusinessUnit');
        /** @var Organization $organization */
        $organization = $this->getEntity($manager, Organization::class, 'mainOrganization');

        foreach (self::$users as $data) {
            $email = new Email();
            $email->setEmail($data['additional_email']);

            $user = new User();
            $user->setUsername($data['username'])
                ->setEmail($data['email'])
                ->setPassword($data['password'])
                ->addEmail($email);

            if ($data['business_unit']) {
                $user->addBusinessUnit($businessUnit);
            }

            if ($data['organization']) {
                $user->setOrganization($organization)->addOrganization($organization);
            }

            $manager->persist($email);
            $manager->persist($user);
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $className
     * @param string $reference
     * @return object
     */
    private function getEntity(ObjectManager $manager, $className, $reference)
    {
        $entity = $manager
            ->getRepository($className)
            ->getFirst();

        $this->setReference($reference, $entity);

        return $entity;
    }
}
