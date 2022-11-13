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
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;

/**
 * Manager for working with entity attributes
 */
class AttributeManager
{
    /** @var ConfigManager */
    private $configManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigTranslationHelper */
    private $configTranslationHelper;

    /** @var ConfigProvider */
    private $extendConfigProvider;

    /** @var FieldConfigModel[] */
    private $attributesByGroup = [];

    /** @var FieldConfigModel[] */
    private $attributesByFamily = [];

    /** @var FieldConfigModel[] */
    private $attributesByClass = [];

    /** @var FieldConfigModel[] */
    private $activeAttributesByClass = [];

    /** @var FieldConfigModel[] */
    private $systemAttributesByClass = [];

    /** @var FieldConfigModel[] */
    private $nonSystemAttributesByClass = [];

    /** @var AttributeFamily[] */
    private $familiesByAttributeId = [];

    private array $sortableOrFilterableAttributesByClass = [];

    public function __construct(
        ConfigManager $configManager,
        DoctrineHelper $doctrineHelper,
        ConfigTranslationHelper $configTranslationHelper
    ) {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->configTranslationHelper = $configTranslationHelper;
    }

    /**
     * @param AttributeGroup $group
     *
     * @return FieldConfigModel[]
     */
    public function getAttributesByGroup(AttributeGroup $group)
    {
        if (!isset($this->attributesByGroup[$group->getId()])) {
            $this->checkDatabase();

            $this->attributesByGroup[$group->getId()] = $this->getRepository()
                ->getAttributesByIds($this->getAttributeIdsByGroup($group));
        }

        return $this->attributesByGroup[$group->getId()];
    }

    /**
     * @param AttributeFamily $family
     *
     * @return FieldConfigModel[]
     */
    public function getAttributesByFamily(AttributeFamily $family)
    {
        if (!isset($this->attributesByFamily[$family->getId()])) {
            $this->checkDatabase();

            $this->attributesByFamily[$family->getId()] = $this->getRepository()
                ->getAttributesByIds($this->getAttributeIdsByFamily($family));
        }

        return $this->attributesByFamily[$family->getId()];
    }

    /**
     * @param array $ids
     *
     * @return FieldConfigModel[]
     */
    public function getAttributesByIdsWithIndex(array $ids)
    {
        $this->checkDatabase();

        return $this->getRepository()->getAttributesByIdsWithIndex($ids);
    }

    /**
     * @param string $className
     *
     * @return FieldConfigModel[]
     */
    public function getAttributesByClass($className)
    {
        if (!isset($this->attributesByClass[$className])) {
            $this->checkDatabase();

            $this->attributesByClass[$className] = $this->getRepository()->getAttributesByClass($className);
        }

        return $this->attributesByClass[$className];
    }

    /**
     * @param string $className
     *
     * @return FieldConfigModel[]
     */
    public function getActiveAttributesByClass($className)
    {
        if (!isset($this->activeAttributesByClass[$className])) {
            $this->checkDatabase();

            $this->activeAttributesByClass[$className] = $this->getActiveAttributesByClassName($className);
        }

        return $this->activeAttributesByClass[$className];
    }

    /**
     * @param string $className
     * @param OrganizationInterface $organization
     * @return FieldConfigModel[]
     */
    public function getActiveAttributesByClassForOrganization(string $className, OrganizationInterface $organization)
    {
        return $this->getActiveAttributesByClass($className);
    }

    /**
     * @param string $className
     *
     * @return FieldConfigModel[]
     */
    protected function getActiveAttributesByClassName(string $className): array
    {
        return $this->getRepository()->getActiveAttributesByClass($className);
    }

    /**
     * @param string $className
     *
     * @return FieldConfigModel[]
     */
    public function getSystemAttributesByClass($className)
    {
        if (!isset($this->systemAttributesByClass[$className])) {
            $this->checkDatabase();

            $this->systemAttributesByClass[$className] = $this->getRepository()
                ->getAttributesByClassAndIsSystem($className, 1);
        }

        return $this->systemAttributesByClass[$className];
    }

    /**
     * @param string $className
     *
     * @return FieldConfigModel[]
     */
    public function getNonSystemAttributesByClass($className)
    {
        if (!isset($this->nonSystemAttributesByClass[$className])) {
            $this->checkDatabase();

            $this->nonSystemAttributesByClass[$className] = $this->getRepository()
                ->getAttributesByClassAndIsSystem($className, 0);
        }

        return $this->nonSystemAttributesByClass[$className];
    }

    public function getSortableOrFilterableAttributesByClass(string $className, array $families): array
    {
        if (!isset($this->sortableOrFilterableAttributesByClass[$className])) {
            $this->checkDatabase();

            $ids = $this->getAttributeGroupRelationRepository()->getAttributesIdsByFamilies($families);
            $attributes = $this->getRepository()->getSortableOrFilterableAttributes($className, $ids);
            $this->sortableOrFilterableAttributesByClass[$className] = $attributes;
        }

        return $this->sortableOrFilterableAttributesByClass[$className];
    }

    /**
     * @param integer $attributeId
     *
     * @return AttributeFamily[]
     */
    public function getFamiliesByAttributeId($attributeId)
    {
        if (!isset($this->familiesByAttributeId[$attributeId])) {
            $this->familiesByAttributeId[$attributeId] = $this->getAttributeFamilyRepository()
                ->getFamiliesByAttributeId($attributeId);
        }

        return $this->familiesByAttributeId[$attributeId];
    }

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isSystem(FieldConfigModel $attribute)
    {
        return $this->getExtendConfigProvider()
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->is('owner', ExtendScope::OWNER_SYSTEM);
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
     *
     * @return string
     */
    public function getAttributeLabel(FieldConfigModel $attribute)
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');
        $labelValue = $entityConfigProvider
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->get('label');

        $labelTranslation = $this->configTranslationHelper
            ->translateWithFallback($labelValue, $attribute->getFieldName());

        return $labelTranslation;
    }

    /**
     * @param array $groupIds
     *
     * @return array, [group_id => [attribute_id_1, attribute_id_2, ...], ...]
     */
    public function getAttributesMapByGroupIds(array $groupIds)
    {
        return $this->getAttributeGroupRelationRepository()->getAttributesMapByGroupIds($groupIds);
    }

    /**
     * @param AttributeFamily $attributeFamily
     *
     * @return array [['group' => $group1, 'attributes' => [$attribute1, $attribute2, ...]], ...]
     */
    public function getGroupsWithAttributes(AttributeFamily $attributeFamily)
    {
        /** @var AttributeGroupRepository $groupRepository */
        $groupRepository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeGroup::class);
        $groups = $groupRepository->getGroupsWithAttributeRelations($attributeFamily);
        $attributes = $this->getAttributesByFamily($attributeFamily);

        $data = [];
        foreach ($groups as $group) {
            $item = ['group' => $group, 'attributes' => []];
            /** @var AttributeGroupRelation $attributeRelation */
            foreach ($group->getAttributeRelations() as $attributeRelation) {
                $item['attributes'][] = $attributes[$attributeRelation->getEntityConfigFieldId()];
            }
            $data[] = $item;
        }

        return $data;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @param $attributeName
     *
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

    /**
     * @param AttributeFamily $family
     *
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
     *
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
    protected function getRepository()
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
     * @return ConfigProvider
     */
    private function getExtendConfigProvider()
    {
        if (!$this->extendConfigProvider) {
            $this->extendConfigProvider = $this->configManager->getProvider('extend');
        }

        return $this->extendConfigProvider;
    }

    public function clearAttributesCache()
    {
        $this->attributesByGroup = [];
        $this->attributesByFamily = [];
        $this->attributesByClass = [];
        $this->activeAttributesByClass = [];
        $this->systemAttributesByClass = [];
        $this->nonSystemAttributesByClass = [];
        $this->familiesByAttributeId = [];
    }
}
