<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEntityFields;
use Oro\Bundle\TestFrameworkBundle\Entity\TestExtendedEntity;

/**
 * Creates a TestEntityFields entity with all relevant field types populated
 * (regular ORM fields, M2O/M2M relations, and extended enum/multienum fields)
 * for use in duplication filter integration tests.
 */
class LoadTestEntityFieldsWithExtendData extends AbstractFixture
{
    public const ENTITY = 'test_entity_fields_with_extend_data';
    public const RELATED_ENTITY = 'test_entity_fields_m2o_target';
    public const M2M_ENTITY = 'test_entity_fields_m2m_item';

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $relatedEntity = new TestExtendedEntity();
        $relatedEntity->setRegularField('m2o-target');
        $manager->persist($relatedEntity);
        $this->setReference(self::RELATED_ENTITY, $relatedEntity);

        $m2mEntity = new TestExtendedEntity();
        $m2mEntity->setRegularField('m2m-item');
        $manager->persist($m2mEntity);
        $this->setReference(self::M2M_ENTITY, $m2mEntity);

        /* @var EnumValueRepository $enumRepo */
        $enumRepo = $manager->getRepository(
            ExtendHelper::buildEnumValueClassName('test_entity_fields_enum_field')
        );
        $enumOption = $enumRepo->createEnumValue('Dup Option 1', 1, false, 'dup_opt1');
        $manager->persist($enumOption);

        /** @var EnumValueRepository $multiEnumRepo */
        $multiEnumRepo = $manager->getRepository(
            ExtendHelper::buildEnumValueClassName('test_entity_fields_multienum_field')
        );
        $multiEnumOpt = $multiEnumRepo->createEnumValue('Dup Multi Option 1', 1, false, 'dup_mopt1');
        $manager->persist($multiEnumOpt);

        // Must be flushed before being referenced as a relation
        $manager->flush();

        $entity = new TestEntityFields();
        $entity->setStringField('original');
        $entity->setIntegerField(100);
        $entity->setManyToOneRelation($relatedEntity);
        $entity->addManyToManyRelation($m2mEntity);
        // Extended fields — stored in ExtendEntityStorage, synced to DB via lifecycle listeners
        $entity->set('enum_field', $enumOption);
        $entity->set('multienum_field', new ArrayCollection([$multiEnumOpt]));

        $manager->persist($entity);
        $manager->flush();

        $this->setReference(self::ENTITY, $entity);
    }
}
