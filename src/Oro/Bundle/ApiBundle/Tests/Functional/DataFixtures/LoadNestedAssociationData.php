<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestCustomIdentifier as TestRelatedEntityWithCustomId;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestDefaultAndNull as TestRelatedEntity;
use Oro\Bundle\ApiBundle\Tests\Functional\Environment\Entity\TestEntityForNestedObjects as TestEntity;

class LoadNestedAssociationData extends AbstractFixture
{
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

        $this->addReference('test_entity_1', $this->createEntity(
            $manager,
            'Entity 1',
            TestRelatedEntity::class,
            $this->getReference('test_related_entity_1')->id
        ));
        $this->addReference('test_entity_2', $this->createEntity(
            $manager,
            'Entity 2',
            TestRelatedEntityWithCustomId::class,
            $this->getReference('test_related_entity_with_custom_id_1')->autoincrementKey
        ));
        $this->addReference('test_entity_3', $this->createEntity(
            $manager,
            'Entity 3',
            TestRelatedEntity::class,
            $this->getReference('test_related_entity_2')->id
        ));
        $this->addReference('test_entity_4', $this->createEntity(
            $manager,
            'Entity 4',
            TestRelatedEntityWithCustomId::class,
            $this->getReference('test_related_entity_with_custom_id_2')->autoincrementKey
        ));
        $this->addReference('test_entity_5', $this->createEntity(
            $manager,
            'Entity 5',
            TestRelatedEntity::class,
            $this->getReference('test_related_entity_3')->id
        ));
        $this->addReference('test_entity_6', $this->createEntity(
            $manager,
            'Entity 6',
            TestRelatedEntityWithCustomId::class,
            $this->getReference('test_related_entity_with_custom_id_3')->autoincrementKey
        ));
        $this->addReference('test_entity_7', $this->createEntity(
            $manager,
            'Entity 7',
            null,
            null
        ));

        $manager->flush();
    }

    private function createEntity(
        ObjectManager $manager,
        string $name,
        ?string $relatedClass,
        ?int $relatedId
    ): TestEntity {
        $entity = new TestEntity();
        $entity->setLastName($name);
        $entity->setRelatedClass($relatedClass);
        $entity->setRelatedId($relatedId);
        $manager->persist($entity);

        return $entity;
    }
}
