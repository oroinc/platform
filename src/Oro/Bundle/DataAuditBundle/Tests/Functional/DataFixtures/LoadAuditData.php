<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataAuditBundle\Entity\Audit;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Functional\DataFixtures\LoadUserData;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadAuditData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        /** @var User $user */
        $user = $this->getReference(LoadUserData::SIMPLE_USER);

        $audit = new Audit();
        $audit->setObjectClass(\stdClass::class);
        $audit->setObjectId(999999);
        $audit->setTransactionId(UUIDGenerator::v4());
        $audit->setLoggedAt(new \DateTime('now'));
        $audit->setUser($user);
        $audit->setOrganization($user->getOrganization());
        $audit->setObjectName('stdClass::1');
        $audit->setAction('create');
        $manager->persist($audit);

        $audit = new Audit();
        $audit->setObjectClass(\stdClass::class);
        $audit->setObjectId('ca205501-a584-4e16-bb19-0226cbb9e1c8');
        $audit->setTransactionId(UUIDGenerator::v4());
        $audit->setLoggedAt(new \DateTime('now'));
        $audit->setUser($user);
        $audit->setOrganization($user->getOrganization());
        $audit->setObjectName('stdClass::1');
        $audit->setAction('create');
        $manager->persist($audit);

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadUserData::class];
    }
}
