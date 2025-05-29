<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestRelatedEntityWithCustomId;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;

class LoadNestedAssociationData extends AbstractFixture
{
    #[\Override]
    public function load(ObjectManager $manager)
    {
        for ($i = 1; $i <= 3; $i++) {
            $relatedEntity = new TestRelatedEntity();
            $relatedEntity->withNotBlank = 'Related Entity ' . $i;
            $this->addReference('test_related_entity_' . $i, $relatedEntity);
            $manager->persist($relatedEntity);

            $relatedEntityWithCustomId = new TestRelatedEntityWithCustomId();
            $relatedEntityWithCustomId->key = 'key' . $i;
            $relatedEntityWithCustomId->name = 'Related Entity ' . $i;
            $this->addReference('test_related_entity_with_custom_id_' . $i, $relatedEntityWithCustomId);
            $manager->persist($relatedEntityWithCustomId);
        }
        $manager->flush();

        $entity1 = new TestEntity();
        $entity1->setLastName('Entity 1');
        $entity1->setRelatedClass(TestRelatedEntity::class);
        $entity1->setRelatedId($this->getReference('test_related_entity_1')->id);
        $this->addReference('test_entity_1', $entity1);
        $manager->persist($entity1);

        $entity2 = new TestEntity();
        $entity2->setLastName('Entity 2');
        $entity2->setRelatedClass(TestRelatedEntityWithCustomId::class);
        $entity2->setRelatedId($this->getReference('test_related_entity_with_custom_id_1')->autoincrementKey);
        $this->addReference('test_entity_2', $entity2);
        $manager->persist($entity2);

        $manager->flush();
    }
}
