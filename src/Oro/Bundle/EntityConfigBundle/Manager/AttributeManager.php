<?php

namespace Oro\Bundle\EntityConfigBundle\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\FieldConfigModelRepository;
use Oro\Bundle\EntityConfigBundle\Exception\LogicException;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Symfony\Component\Translation\TranslatorInterface;

class AttributeManager
{
    /**
     * @var ConfigModelManager
     */
    private $configModelManager;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    /**
     * @var ConfigProvider
     */
    private $attributeConfigProvider;

    /**
     * @var ConfigProvider
     */
    private $entityConfigProvider;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param ConfigModelManager $configModelManager
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigProvider $attributeConfigProvider
     * @param ConfigProvider $entityConfigProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigModelManager $configModelManager,
        DoctrineHelper $doctrineHelper,
        ConfigProvider $attributeConfigProvider,
        ConfigProvider $entityConfigProvider,
        TranslatorInterface $translator
    ) {
        $this->configModelManager = $configModelManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->attributeConfigProvider = $attributeConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
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
        return $this->attributeConfigProvider
            ->getConfig($attribute->getEntity()->getClassName(), $attribute->getFieldName())
            ->is('is_system');
    }

    /**
     * @param FieldConfigModel $attribute
     * @return string
     */
    public function getAttributeLabel(FieldConfigModel $attribute)
    {
        $labelValue = $this->entityConfigProvider
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
        $dbCheck = $this->configModelManager->checkDatabase();
        if (!$dbCheck) {
            throw new LogicException(
                'Cannot use config database when a db schema is not synced.'
            );
        }
    }
}
