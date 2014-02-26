<?php

namespace Oro\Bundle\EntityExtendBundle\Extend\Schema;

use Doctrine\DBAL\Schema\Table;

class ExtendTable extends Table
{
    const EXTEND_OPTION_PREFIX_NAME = 'oro_extend';
    const EXTEND_OPTION_PREFIX = 'oro_extend:';

    /**
     * @var ExtendOptionManager
     */
    protected $extendOptionManager;

    /**
     * @param ExtendOptionManager $extendOptionManager
     * @param Table               $baseTable
     */
    public function __construct(ExtendOptionManager $extendOptionManager, Table $baseTable)
    {
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
