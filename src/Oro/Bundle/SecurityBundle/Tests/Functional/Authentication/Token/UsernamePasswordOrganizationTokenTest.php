<?php

namespace Oro\Bundle\SecurityBundle\Tests\Functional\Authentication\Token;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;

class UsernamePasswordOrganizationTokenTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testSerializeReferenceWithoutError()
    {
        $session = $this->getSession();

        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityManagerInterface $objectManager */
        $objectManager = $doctrine->getManager();

        /** @var Organization $organization */
        $organization = $doctrine->getRepository(Organization::class)->findOneBy([]);
        /** @var User $user */
        $user = $doctrine->getRepository(User::class)->findOneBy([]);

        $token = new UsernamePasswordOrganizationToken($user, $user->getPassword(), 'key', $organization);

        $serialized = $token->serialize();
        $token->unserialize($serialized);

        $objectManager->clear();

        $token = new UsernamePasswordOrganizationToken($user, $user->getPassword(), 'key', $organization);
        $serialized = $token->serialize();
        $session->set('serialized', $serialized);
        $token->unserialize($session->get('serialized'));

        $organization = $objectManager->getReference(Organization::class, $organization->getId());
        $user = $objectManager->getReference(User::class, $user->getId());

        $token = new UsernamePasswordOrganizationToken($user, $user->getPassword(), 'key', $organization);
        $session->set('serialized', $serialized);
        $token->unserialize($session->get('serialized'));

        $serialized = $token->serialize();
        $session->set('serialized', $serialized);
        $token->unserialize($session->get('serialized'));
    }
}
