<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataChild;
use Oro\Bundle\DataAuditBundle\Tests\Functional\Environment\Entity\TestAuditDataOwner;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

class LoadTestAuditDataWithOneToManyData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        for ($ownerIndex = 0; $ownerIndex < 3; $ownerIndex++) {
            $owner = new TestAuditDataOwner();
            for ($childIndex = 0; $childIndex < 4; $childIndex++) {
                $child = new TestAuditDataChild();
                $owner->addChildrenOneToMany($child);
                $manager->persist($child);
            }
            $this->addReference('test_audit_owner_' . ($ownerIndex + 1), $owner);
            $manager->persist($owner);
        }
        $manager->flush();
    }
}
