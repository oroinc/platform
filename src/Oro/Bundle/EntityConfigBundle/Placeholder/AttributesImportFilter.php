<?php

namespace Oro\Bundle\EntityConfigBundle\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\WebSocket\AttributesImportTopicSender;

class AttributesImportFilter
{
    /**
     * @var EntityAliasResolver
     */
    private $entityAliasResolver;

    /**
     * @var AttributesImportTopicSender
     */
    private $topicSender;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     * @param AttributesImportTopicSender $topicSender
     * @param ConfigManager $configManager
     */
    public function __construct(
        EntityAliasResolver $entityAliasResolver,
        AttributesImportTopicSender $topicSender,
        ConfigManager $configManager
    ) {
        $this->entityAliasResolver = $entityAliasResolver;
        $this->topicSender = $topicSender;
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityAlias
     * @return bool
     */
    public function isApplicableAlias($entityAlias)
    {
        $entityClass = $this->entityAliasResolver->getClassByAlias($entityAlias);

        return $this->hasEntityClassAttributes($entityClass);
    }

    /**
     * @param object $entity
     * @return bool
     */
    public function isApplicableEntity($entity)
    {
        if (!$entity instanceof EntityConfigModel) {
            return false;
        }

        return $this->hasEntityClassAttributes($entity->getClassName());
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    private function hasEntityClassAttributes(string $entityClass): bool
    {
        $entityConfig = $this->configManager->getEntityConfig('attribute', $entityClass);

        return $entityConfig->is(AttributeConfigHelper::CODE_HAS_ATTRIBUTES);
    }

    /**
     * @param string $entityAlias
     * @return array
     * @throws \LogicException
     */
    public function getTopicByAlias($entityAlias)
    {
        $entityClass = $this->entityAliasResolver->getClassByAlias($entityAlias);
        $entityConfigModel = $this->configManager->getConfigEntityModel($entityClass);

        if (!$entityConfigModel) {
            throw new \LogicException(sprintf('No entity config model found for class %s', $entityClass));
        }

        return $this->getTopicByEntity($entityConfigModel);
    }

    /**
     * @param EntityConfigModel $entity
     * @return array
     */
    public function getTopicByEntity(EntityConfigModel $entity)
    {
        return ['topic' => $this->topicSender->getTopic($entity->getId())];
    }
}
