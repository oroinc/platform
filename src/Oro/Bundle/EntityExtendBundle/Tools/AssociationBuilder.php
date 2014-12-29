<?php

namespace Oro\Bundle\EntityExtendBundle\Tools;

use Doctrine\ORM\Mapping\MappingException as ORMMappingException;
use Doctrine\Common\Persistence\Mapping\MappingException as PersistenceMappingException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class AssociationBuilder
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var RelationBuilder */
    protected $relationBuilder;

    /**
     * @param ConfigManager   $configManager
     * @param RelationBuilder $relationBuilder
     */
    public function __construct(
        ConfigManager $configManager,
        RelationBuilder $relationBuilder
    ) {
        $this->configManager   = $configManager;
        $this->relationBuilder = $relationBuilder;
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToManyAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);

        // add relation to owning entity
        $this->relationBuilder->addManyToManyRelation(
            $this->configManager->getProvider('extend')->getConfig($sourceEntityClass),
            $targetEntityClass,
            $relationName,
            $targetEntityPrimaryKeyColumns,
            $targetEntityPrimaryKeyColumns,
            $targetEntityPrimaryKeyColumns,
            [
                'extend' => [
                    'without_default' => true,
                ],
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => true
                ],
                'form'   => [
                    'is_enabled' => true
                ]
            ]
        );
    }

    /**
     * @param string $sourceEntityClass
     * @param string $targetEntityClass
     * @param string $associationKind
     */
    public function createManyToOneAssociation($sourceEntityClass, $targetEntityClass, $associationKind)
    {
        $relationName = ExtendHelper::buildAssociationName($targetEntityClass, $associationKind);

        $entityConfigProvider = $this->configManager->getProvider('entity');
        $targetEntityConfig   = $entityConfigProvider->getConfig($targetEntityClass);

        $label       = $targetEntityConfig->get(
            'label',
            false,
            ConfigHelper::getTranslationKey('entity', 'label', $targetEntityClass, $relationName)
        );
        $description = ConfigHelper::getTranslationKey('entity', 'description', $targetEntityClass, $relationName);

        $targetEntityPrimaryKeyColumns = $this->getPrimaryKeyColumnNames($targetEntityClass);
        $targetFieldName               = array_shift($targetEntityPrimaryKeyColumns);

        // add relation to owning entity
        $this->relationBuilder->addManyToOneRelation(
            $this->configManager->getProvider('extend')->getConfig($sourceEntityClass),
            $targetEntityClass,
            $relationName,
            $targetFieldName,
            [
                'entity' => [
                    'label'       => $label,
                    'description' => $description,
                ],
                'view'   => [
                    'is_displayable' => false
                ],
                'form'   => [
                    'is_enabled' => false
                ]
            ]
        );
    }

    /**
     * @param string $entityClass
     *
     * @return string[]
     */
    protected function getPrimaryKeyColumnNames($entityClass)
    {
        try {
            return $this->configManager
                ->getEntityManager()
                ->getClassMetadata($entityClass)
                ->getIdentifierColumnNames();
        } catch (\ReflectionException $e) {
            // ignore entity not found exception
            return ['id'];
        }
            // ignore any doctrine mapping exceptions
            // it may happens if the entity has relation to deleted custom entity
            // or during update schema for newly created custom entity with relation
        catch (ORMMappingException $e) {
            return ['id'];
        } catch (PersistenceMappingException $e) {
            return ['id'];
        }
    }
}
