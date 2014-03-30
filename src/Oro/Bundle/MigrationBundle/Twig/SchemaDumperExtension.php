<?php

namespace Oro\Bundle\MigrationBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class SchemaDumperExtension extends \Twig_Extension
{
    /**
     * @var AbstractPlatform
     */
    protected $platform;

    /**
     * @var Column
     */
    protected $defaultColumn;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'schema_dumper_extension';
    }

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $doctrine->getConnection();
        $this->platform = $connection->getDatabasePlatform();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'oro_migration_get_schema_column_options' => new \Twig_Function_Method($this, 'getColumnOptions'),
        );
    }

    /**
     * @param Column $column
     * @return array
     */
    public function getColumnOptions(Column $column)
    {
        if (!$this->defaultColumn) {
            $this->defaultColumn = new Column('_template_', Type::getType(Type::STRING));
        }

        $optionNames = [
            'default',
            'notnull',
            'length',
            'precision',
            'scale',
            'fixed',
            'unsigned',
            'autoincrement'
        ];

        $options = [];
        foreach ($optionNames as $optionName) {
            $defaultValue = $this->getColumnOption($this->defaultColumn, $optionName);
            $value = $this->getColumnOption($column, $optionName);
            if ($value !== $defaultValue) {
                $options[$optionName] = $value;
            }
        }
        $comment = $column->getComment();
        if ($this->platform && $this->platform->isCommentedDoctrineType($column->getType())) {
            $comment .= $this->platform->getDoctrineTypeComment($column->getType());
        }
        if (!empty($comment)) {
            $options['comment'] = $comment;
        }

        return $options;
    }

    /**
     * @param Column $column
     * @param string $optionName
     * @return mixed
     */
    protected function getColumnOption(Column $column, $optionName)
    {
        $method = "get" . $optionName;

        return $column->$method();
    }
}
