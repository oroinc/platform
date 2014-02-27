<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

class ExtendTable extends Table
{
    const EXTEND_OPTION_PREFIX_NAME = 'oro_options';
    const EXTEND_OPTION_PREFIX      = 'oro_options:';

    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /** @var  ExtendSchema */
    protected $schema;

    /**
     * @param ExtendSchema        $schema
     * @param ExtendOptionManager $extendOptionManager
     * @param Table               $baseTable
     */
    public function __construct(ExtendSchema $schema, ExtendOptionManager $extendOptionManager, Table $baseTable)
    {
        $this->schema              = $schema;
        $this->extendOptionManager = $extendOptionManager;

        parent::__construct(
            $baseTable->getName(),
            $baseTable->getColumns(),
            $baseTable->getIndexes(),
            $baseTable->getForeignKeys(),
            false,
            $baseTable->getOptions()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function addOption($name, $value)
    {
        if (0 === strpos($name, self::EXTEND_OPTION_PREFIX_NAME)) {
            if (strlen(self::EXTEND_OPTION_PREFIX_NAME) === strlen($name)) {
                $this->extendOptionManager->addTableOptions(
                    $this->getName(),
                    $value
                );

                return $this;
            } elseif (0 === strpos($name, self::EXTEND_OPTION_PREFIX)) {
                $this->extendOptionManager->addTableOption(
                    $this->getName(),
                    substr($name, strlen(self::EXTEND_OPTION_PREFIX)),
                    $value
                );

                return $this;
            }
        }

        return parent::addOption($name, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function addColumn($columnName, $typeName, array $options = array())
    {
        switch ($typeName) {
            case 'manyToOne':

                break;
            case 'oneToMany':
                $selfColumnName     = ExtendConfigDumper::DEFAULT_PREFIX . $columnName . '_id';
                $selfTableName      = $this->getName();
                $selfClassName      =
                    $this->extendOptionManager->getEntityClassResolver()->getEntityClassByTableName($selfTableName);
                $selfClassNameParts = explode('\\', $selfClassName);

                $targetTableName = $options[self::EXTEND_OPTION_PREFIX_NAME]['extend']['target']['table_name'];
                if (!$this->schema->hasTable($targetTableName)) {
                    throw new \RuntimeException(sprintf('Table "%s" do NOT exists.', $targetTableName));
                }
                $targetTable      = $this->schema->getTable($targetTableName);
                $targetColumnName =
                    ExtendConfigDumper::FIELD_PREFIX .
                    strtolower(array_pop($selfClassNameParts)) .
                    '_' .
                    $columnName .
                    '_id';

                parent::addColumn($selfColumnName, 'integer', ['notnull' => false]);
                parent::addForeignKeyConstraint(
                    $targetTableName,
                    [$selfColumnName],
                    $targetTable->getPrimaryKey()->getColumns()
                );
                $targetTable->addColumn($targetColumnName, 'integer', ['notnull' => false]);
                $targetTable->addForeignKeyConstraint($selfTableName, [$targetColumnName], [$selfColumnName]);

                break;
            case 'manyToMany':

                break;
        }

        if (!isset($options[self::EXTEND_OPTION_PREFIX_NAME])
            && $this->extendOptionManager->isConfigurableEntity($this->getName())
        ) {
            $options[self::EXTEND_OPTION_PREFIX_NAME] = [];
        }

        foreach ($options as $name => $value) {
            if ($name === self::EXTEND_OPTION_PREFIX_NAME) {
                $this->extendOptionManager->addColumnOptions(
                    $this->getName(),
                    $columnName,
                    $typeName,
                    $value
                );
                unset($options[$name]);

                $columnName         = ExtendConfigDumper::FIELD_PREFIX . $columnName;
                $options['notnull'] = false;
            }
        }

        if (in_array($typeName, ['oneToMany', 'manyToOne', 'manyToMany', 'optionSet'])) {
            return null;
        }

        return parent::addColumn($columnName, $typeName, $options);
    }

    public function setSchema(ExtendSchema $schema)
    {
        $this->schema = $schema;
    }
}
