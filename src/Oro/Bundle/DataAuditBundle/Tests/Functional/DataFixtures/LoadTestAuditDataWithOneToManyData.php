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
    private const CHILD_COUNT = 4;

    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $owner = new TestAuditDataOwner();
        for ($childIndex = 1; $childIndex <= self::CHILD_COUNT; $childIndex++) {
            $child = new TestAuditDataChild();
            $owner->addChildrenOneToMany($child);
            $manager->persist($child);
        }

        $manager->persist($owner);
        $manager->flush();

        $this->addReference('testAuditOwner', $owner);
    }
}
