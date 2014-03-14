<?php

namespace Oro\Bundle\MigrationBundle\Migration\Schema;

use Doctrine\DBAL\Schema\Column as BaseColumn;

/**
 * The aim of this class is to provide a way extend doctrine Column class which can be used in migrations
 */
class Column extends BaseColumn
{
    /**
     * Constructor
     *
     * @param array $args
     */
    public function __construct(array $args)
    {
        /** @var BaseColumn $baseColumn */
        $baseColumn = $args['column'];

        $optionNames = [
            'Length',
            'Precision',
            'Scale',
            'Unsigned',
            'Fixed',
            'Notnull',
            'Default',
            'Autoincrement',
            'Comment'
        ];

        $options = [];
        foreach ($optionNames as $name) {
            $method = "get" . $name;
            $val    = $baseColumn->$method();
            if ($this->$method() !== $val) {
                $options[$name] = $val;
            }
        }
        $this->_setName($baseColumn->getName());
        $this->_type = $baseColumn->getType();
        $this->setOptions($options);
        $this->setColumnDefinition($baseColumn->getColumnDefinition());
        $this->setPlatformOptions($baseColumn->getPlatformOptions());
        $this->setCustomSchemaOptions($baseColumn->getCustomSchemaOptions());
    }

    /**
     * Change a name of this column
     *
     * @param string $newName
     */
    public function changeName($newName)
    {
        $this->_setName($newName);
    }
}
