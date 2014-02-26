<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Table as BaseTable;

class Table extends BaseTable
{
    const EXTEND_OPTION_PREFIX_NAME = 'extend';
    const EXTEND_OPTION_PREFIX = 'extend:';

    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @param ExtendOptionManager $extendOptionManager
     * @param BaseTable           $table
     */
    public function __construct(ExtendOptionManager $extendOptionManager, BaseTable $table)
    {
        $this->extendOptionManager = $extendOptionManager;

        parent::__construct(
            $table->getName(),
            $table->getColumns(),
            $table->getIndexes(),
            $table->getForeignKeys(),
            false,
            $table->getOptions()
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
        foreach ($options as $name => $value) {
            if (0 === strpos($name, self::EXTEND_OPTION_PREFIX_NAME)) {
                if (strlen(self::EXTEND_OPTION_PREFIX_NAME) === strlen($name)) {
                    $this->extendOptionManager->addColumnOptions(
                        $this->getName(),
                        $columnName,
                        $typeName,
                        $value
                    );
                    unset($options[$name]);
                } elseif (0 === strpos($name, self::EXTEND_OPTION_PREFIX)) {
                    $this->extendOptionManager->addColumnOption(
                        $this->getName(),
                        $columnName,
                        $typeName,
                        substr($name, strlen(self::EXTEND_OPTION_PREFIX)),
                        $value
                    );
                    unset($options[$name]);
                }
            }
        }

        return parent::addColumn($columnName, $typeName, $options);
    }
}
