<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;

class LoadNestedAssociationData extends AbstractFixture
{
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 3; $i++) {
            $relatedEntity = new TestRelatedEntity();
            $this->addReference('test_related_entity' . $i, $relatedEntity);
            $manager->persist($relatedEntity);
        }
        $manager->flush();

        $entity = new TestEntity();
        $entity->setLastName('test');
        $entity->setRelatedClass(TestRelatedEntity::class);
        $entity->setRelatedId($this->getReference('test_related_entity1')->id);
        $this->addReference('test_entity', $entity);
        $manager->persist($entity);
        $manager->flush();
    }
}
