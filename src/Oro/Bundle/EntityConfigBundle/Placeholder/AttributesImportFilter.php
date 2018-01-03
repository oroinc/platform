<?php

namespace Oro\Bundle\EntityConfigBundle\Placeholder;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\AttributeConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\EventListener\AttributesImportFinishNotificationListener;
use Oro\Bundle\SyncBundle\Content\SimpleTagGenerator;
use Oro\Bundle\SyncBundle\Content\TagGeneratorInterface;

class AttributesImportFilter
{
    /**
     * @var EntityAliasResolver
     */
    private $entityAliasResolver;

    /**
     * @var TagGeneratorInterface
     */
    private $tagGenerator;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     * @param TagGeneratorInterface $tagGenerator
     * @param ConfigManager $configManager
     */
    public function __construct(
        EntityAliasResolver $entityAliasResolver,
        TagGeneratorInterface $tagGenerator,
        ConfigManager $configManager
    ) {
        $this->entityAliasResolver = $entityAliasResolver;
        $this->tagGenerator = $tagGenerator;
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityAlias
     * @return bool
     */
    public function isApplicableAlias($entityAlias)
    {
        $entityClass = $this->entityAliasResolver->getClassByAlias($entityAlias);

        return $this->hasEntityClassAtrributes($entityClass);
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

        return $this->hasEntityClassAtrributes($entity->getClassName());
    }

    /**
     * @param string $entityClass
     * @return bool
     */
    private function hasEntityClassAtrributes(string $entityClass): bool
    {
        $entityConfig = $this->configManager->getEntityConfig('attribute', $entityClass);

        return $entityConfig->is(AttributeConfigHelper::CODE_HAS_ATTRIBUTES);
    }

    /**
     * @param string $entityAlias
     * @return array
     * @throws \LogicException
     */
    public function getTagByAlias($entityAlias)
    {
        $entityClass = $this->entityAliasResolver->getClassByAlias($entityAlias);
        $entityConfigModel = $this->configManager->getConfigEntityModel($entityClass);

        if (!$entityConfigModel) {
            throw new \LogicException(sprintf('No entity config model found for class %s', $entityClass));
        }

        return $this->generateTag($entityConfigModel);
    }

    /**
     * @param EntityConfigModel $entity
     * @return array
     * @throws \InvalidArgumentException
     */
    public function getTagByEntity(EntityConfigModel $entity)
    {
        return $this->generateTag($entity);
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @return array
     */
    private function generateTag($entityConfigModel)
    {
        return $this->tagGenerator->generate([
            SimpleTagGenerator::STATIC_NAME_KEY
            => AttributesImportFinishNotificationListener::ATTRIBUTE_IMPORT_FINISH_TAG,
            SimpleTagGenerator::IDENTIFIER_KEY => [$entityConfigModel->getId()]
        ]);
    }
}