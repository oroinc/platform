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
    public const ENUM_OPTION = 'test_entity_fields_enum_option';
    public const RELATED_BAG_TARGET = 'test_entity_fields_related_bag_m2o_target';
    public const RELATED_ENUM_OPTION = 'test_entity_fields_related_enum_option';

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
        $this->setReference(self::ENUM_OPTION, $enumOption);

        /** @var EnumValueRepository $multiEnumRepo */
        $multiEnumRepo = $manager->getRepository(
            ExtendHelper::buildEnumValueClassName('test_entity_fields_multienum_field')
        );
        $multiEnumOpt = $multiEnumRepo->createEnumValue('Dup Multi Option 1', 1, false, 'dup_mopt1');
        $manager->persist($multiEnumOpt);

        /** @var EnumValueRepository $relatedEnumRepo */
        $relatedEnumRepo = $manager->getRepository(
            ExtendHelper::buildEnumValueClassName('test_extended_entity_enum_attribute')
        );
        $relatedEnumOption = $relatedEnumRepo->createEnumValue('Dup Nested Option 1', 1, false, 'dup_nested_opt1');
        $manager->persist($relatedEnumOption);
        $this->setReference(self::RELATED_ENUM_OPTION, $relatedEnumOption);

        $bagTarget = new TestEntityFields();
        $bagTarget->setStringField('bag-target');
        $manager->persist($bagTarget);
        $this->setReference(self::RELATED_BAG_TARGET, $bagTarget);

        // Must be flushed before being referenced as a relation
        $manager->flush();

        // Extended storage of the related entity: an object, a collection and serialized values
        $relatedEntity->set('oro_test_framework_test_entity_fields', $bagTarget);
        $relatedEntity->set('biM2MOwners', new ArrayCollection());
        $relatedEntity->set('serialized_attribute', 'nested-serialized');
        $relatedEntity->set('testExtendedEntityEnumAttribute', $relatedEnumOption);

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
