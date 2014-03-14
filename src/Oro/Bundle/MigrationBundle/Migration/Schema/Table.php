<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Doctrine\DBAL\Schema\Table as BaseTable;
use Doctrine\DBAL\Schema\Column as BaseColumn;

/**
 * The aim of this class is to provide a way extend doctrine Column class
 * To do this just define your column class name in COLUMN_CLASS constant in an extended class
 * and override createColumnObject if your column class constructor need an additional arguments
 */
class Table extends BaseTable
{
    /**
     * Used column class, define COLUMN_CLASS constant in an extended class to extend the column class
     * Important: your class must extend Oro\Bundle\MigrationBundle\Migration\Schema\Column class
     *            or extend Doctrine\DBAL\Schema\Column class and must have __construct(array $args) method
     */
    const COLUMN_CLASS = 'Doctrine\DBAL\Schema\Column';

    /**
     * Creates an instance of COLUMN_CLASS class
     *
     * @param array $args An arguments for COLUMN_CLASS class constructor
     *                    An instance of a base column is in 'column' element
     * @return BaseColumn
     */
    protected function createColumnObject(array $args)
    {
        $columnClass = static::COLUMN_CLASS;

        return new $columnClass($args);
    }

    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        /** @var BaseTable $baseTable */
        $baseTable = $args['table'];

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
    public function addColumn($columnName, $typeName, array $options = [])
    {
        parent::addColumn($columnName, $typeName, $options);

        return $this->getColumn($columnName);
    }

    /**
     * {@inheritdoc}
     */
    // @codingStandardsIgnoreStart
    protected function _addColumn(BaseColumn $column)
    {
        if (get_class($column) !== static::COLUMN_CLASS && static::COLUMN_CLASS !== 'Doctrine\DBAL\Schema\Column') {
            $column = $this->createColumnObject(['column' => $column]);
        }
        parent::_addColumn($column);
    }
    // @codingStandardsIgnoreEnd
}
