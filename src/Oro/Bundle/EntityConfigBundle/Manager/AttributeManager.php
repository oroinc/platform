<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Translation\TranslatorInterface;

class AttributeManager
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var ConfigProvider
     */
    private $extendConfigProvider;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param AttributeGroup $group
     * @return FieldConfigModel[]
     */
    public function getAttributesByGroup(AttributeGroup $group)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByIds($this->getAttributeIdsByGroup($group));
    }

    /**
     * @param AttributeFamily $family
     * @return FieldConfigModel[]
     */
    public function getAttributesByFamily(AttributeFamily $family)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByIds($this->getAttributeIdsByFamily($family));
    }

    /**
     * @param array $ids
     * @return FieldConfigModel[]
     */
    public function getAttributesByIdsWithIndex(array $ids)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByIdsWithIndex($ids);
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getAttributesByClass($className)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByClass($className);
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getActiveAttributesByClass($className)
    {
        $this->checkDatabase();

        return $this->getRepository()->getActiveAttributesByClass($className);
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getSystemAttributesByClass($className)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByClassAndIsSystem($className, 1);
    }

    /**
     * @param string $className
     * @return FieldConfigModel[]
     */
    public function getNonSystemAttributesByClass($className)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByClassAndIsSystem($className, 0);
    }

    /**
     * @param integer $attributeId
     * @return AttributeFamily[]
     */
    public function getFamiliesByAttributeId($attributeId)
    {
        return $this->getAttributeFamilyRepository()->getFamiliesByAttributeId($attributeId);
    }

    /**
     * @param FieldConfigModel $attribute
     * @return bool
     */
    public function isSystem(FieldConfigModel $attribute)
    {
        return $this->getExtendConfigProvider()
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->is('owner', ExtendScope::OWNER_SYSTEM);
    }

    /**
     * @return ConfigProvider
     */
    private function getExtendConfigProvider()
    {
        if (!$this->extendConfigProvider) {
            $this->extendConfigProvider = $this->configManager->getProvider('extend');
        }

        return $this->extendConfigProvider;
    }

    /**
     * @param FieldConfigModel $attribute
     * @return bool
     */
    public function isActive(FieldConfigModel $attribute)
    {
        return $this->getExtendConfigProvider()
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]);
    }

    /**
     * @param FieldConfigModel $attribute
     * @return string
     */
    public function getAttributeLabel(FieldConfigModel $attribute)
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $labelValue = $entityConfigProvider
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->get('label');

        //TODO: Extract this translation logic to a separate helper class and reuse in ConfigSubscriber
        if ($this->translator->hasTrans($labelValue)) {
            $labelTranslation = $this->translator->trans($labelValue);
        } else {
            $labelTranslation = $attribute->getFieldName();
        }

        return $labelTranslation;
    }

    /**
     * @param array $groupIds
     * @return array, [group_id => [attribute_id_1, attribute_id_2, ...], ...]
     */
    public function getAttributesMapByGroupIds(array $groupIds)
    {
        return $this->getAttributeGroupRelationRepository()->getAttributesMapByGroupIds($groupIds);
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return array [['group' => $group1, 'attributes' => [$attribute1, $attribute2, ...]], ...]
     */
    public function getGroupsWithAttributes(AttributeFamily $attributeFamily)
    {
        /** @var AttributeGroupRepository $groupRepository */
        $groupRepository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeGroup::class);
        $groups = $groupRepository->getGroupsWithAttributeRelations($attributeFamily);
        $attributes = $this->getAttributesByFamily($attributeFamily);

        $data = [];
        /** @var AttributeGroup $group */
        foreach ($groups as $group) {
            $item = ['group' => $group, 'attributes' => []];
            /** @var AttributeGroupRelation$attributeRelation */
            foreach ($group->getAttributeRelations() as $attributeRelation) {
                $item['attributes'][] = $attributes[$attributeRelation->getEntityConfigFieldId()];
            }
            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param AttributeFamily $family
     * @return array
     */
    private function getAttributeIdsByFamily(AttributeFamily $family)
    {
        $ids = [];
        foreach ($family->getAttributeGroups() as $attributeGroup) {
            $ids = array_merge($ids, $this->getAttributeIdsByGroup($attributeGroup));
        }

        return $ids;
    }

    /**
     * @param AttributeGroup $group
     * @return array
     */
    private function getAttributeIdsByGroup(AttributeGroup $group)
    {
        return $group->getAttributeRelations()
            ->map(function (AttributeGroupRelation $relation) {
                return $relation->getEntityConfigFieldId();
            })
            ->toArray();
    }

    /**
     * @return FieldConfigModelRepository
     */
    private function getRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(FieldConfigModel::class);
    }

    /**
     * @return AttributeFamilyRepository
     */
    private function getAttributeFamilyRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AttributeFamily::class);
    }

    /**
     * @return AttributeGroupRelationRepository
     */
    private function getAttributeGroupRelationRepository()
    {
        return $this->doctrineHelper->getEntityRepositoryForClass(AttributeGroupRelation::class);
    }

    /**
     * @throws LogicException
     */
    private function checkDatabase()
    {
        if (!$this->configManager->isDatabaseReadyToWork()) {
            throw new LogicException(
                'Cannot use config database when a db schema is not synced.'
            );
        }
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param $attributeName
     * @return null|FieldConfigModel
     */
    public function getAttributeByFamilyAndName(AttributeFamily $attributeFamily, $attributeName)
    {
        $attributes = $this->getAttributesByFamily($attributeFamily);

        foreach ($attributes as $attribute) {
            if ($attribute->getFieldName() === $attributeName) {
                return $attribute;
            }
        }

        return null;
    }
}
