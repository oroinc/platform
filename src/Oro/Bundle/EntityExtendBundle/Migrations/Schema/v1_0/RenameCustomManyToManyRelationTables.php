<?php

namespace Oro\Bundle\EntityExtendBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;
use Oro\Bundle\EntityExtendBundle\Tools\DbIdentifierNameGenerator as ExtendDbIdentifierNameGenerator;
use Oro\Bundle\MigrationBundle\Migration\Extension\NameGeneratorAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtension;
use Oro\Bundle\MigrationBundle\Migration\Extension\RenameExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Tools\DbIdentifierNameGenerator;

class RenameCustomManyToManyRelationTables implements
    Migration,
    RenameExtensionAwareInterface,
    NameGeneratorAwareInterface,
    ContainerAwareInterface
{
    /**
     * @var RenameExtension
     */
    protected $renameExtension;

    /**
     * @var ExtendDbIdentifierNameGenerator
     */
    protected $nameGenerator;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setRenameExtension(RenameExtension $renameExtension)
    {
        $this->renameExtension = $renameExtension;
    }

    /**
     * @inheritdoc
     */
    public function setNameGenerator(DbIdentifierNameGenerator $nameGenerator)
    {
        $this->nameGenerator = $nameGenerator;
    }

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->container->get('oro_entity_config.config_manager');

        /** @var EntityConfigId[] $entityConfigIds */
        $entityConfigIds = $configManager->getIds('extend');
        foreach ($entityConfigIds as $entityConfigId) {
            if ($configManager->getConfig($entityConfigId)->is('is_extend')) {
                /** @var FieldConfigId[] $fieldConfigIds */
                $fieldConfigIds = $configManager->getIds('extend', $entityConfigId->getClassName());
                foreach ($fieldConfigIds as $fieldConfigId) {
                    if ($fieldConfigId->getFieldType() === 'manyToMany') {
                        $fieldConfig = $configManager->getConfig($fieldConfigId);
                        $targetClassName = $fieldConfig->get('target_entity');
                        $oldTableName = $this->generateOldManyToManyJoinTableName(
                            $fieldConfigId->getClassName(),
                            $fieldConfigId->getFieldName(),
                            $targetClassName
                        );
                        if ($schema->hasTable($oldTableName)) {
                            $newTableName = $this->nameGenerator->generateManyToManyJoinTableName(
                                $fieldConfigId->getClassName(),
                                $fieldConfigId->getFieldName(),
                                $targetClassName
                            );
                            $this->renameExtension->renameTable(
                                $schema,
                                $queries,
                                $oldTableName,
                                $newTableName
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Builds old table name for many-to-many relation
     *
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $targetEntityClassName
     * @return string
     */
    public function generateOldManyToManyJoinTableName($entityClassName, $fieldName, $targetEntityClassName)
    {
        $parts     = explode('\\', $entityClassName);
        $className = array_pop($parts);

        $targetParts     = explode('\\', $targetEntityClassName);
        $targetClassName = array_pop($targetParts);

        return strtolower('oro_' . $className . '_' . $targetClassName . '_' . $fieldName);
    }
}
