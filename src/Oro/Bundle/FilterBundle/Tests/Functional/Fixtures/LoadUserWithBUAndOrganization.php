<?php

namespace Oro\Bundle\FilterBundle\Tests\Functional\Fixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\Email;
use Oro\Bundle\UserBundle\Entity\User;

class LoadUserWithBUAndOrganization extends AbstractFixture implements DependentFixtureInterface
{
    private static array $users = [
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

    #[\Override]
    public function getDependencies(): array
    {
        return [LoadOrganization::class, LoadBusinessUnit::class];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var BusinessUnit $businessUnit */
        $businessUnit = $this->getReference(LoadBusinessUnit::BUSINESS_UNIT);
        $this->setReference('mainBusinessUnit', $businessUnit);
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $this->setReference('mainOrganization', $organization);

        foreach (self::$users as $data) {
            $email = new Email();
            $email->setEmail($data['additional_email']);

            $user = new User();
            $user->setUsername($data['username']);
            $user->setEmail($data['email']);
            $user->setPassword($data['password']);
            $user->addEmail($email);

            if ($data['business_unit']) {
                $user->addBusinessUnit($businessUnit);
            }

            if ($data['organization']) {
                $user->setOrganization($organization)->addOrganization($organization);
            }

            $manager->persist($email);
            $manager->persist($user);

            $this->setReference($data['email'], $user);
        }
        $manager->flush();
    }
}
