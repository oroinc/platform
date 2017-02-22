<?php

namespace Oro\Bundle\MigrationBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class SchemaDumperExtension extends \Twig_Extension
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var AbstractPlatform */
    protected $platform;

    /** @var Column */
    protected $defaultColumn;

    /** @var array */
    protected $defaultColumnOptions = [];

    /** @var array */
    protected $optionNames = [
        'default',
        'notnull',
        'length',
        'precision',
        'scale',
        'fixed',
        'unsigned',
        'autoincrement'
    ];

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'schema_dumper_extension';
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_migration_get_schema_column_options', [$this, 'getColumnOptions']),
        ];
    }

    /**
     * @param Column $column
     * @return array
     */
    public function getColumnOptions(Column $column)
    {
        $defaultOptions = $this->getDefaultOptions();
        $platform = $this->getPlatform();
        $options = [];

        foreach ($this->optionNames as $optionName) {
            $value = $this->getColumnOption($column, $optionName);
            if ($value !== $defaultOptions[$optionName]) {
                $options[$optionName] = $value;
            }
        }

        $comment = $column->getComment();
        if ($platform && $platform->isCommentedDoctrineType($column->getType())) {
            $comment .= $platform->getDoctrineTypeComment($column->getType());
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

    /**
     * @return AbstractPlatform
     */
    protected function getPlatform()
    {
        if (!$this->platform) {
            $this->platform = $this->doctrine->getConnection()->getDatabasePlatform();
        }

        return $this->platform;
    }

    /**
     * @return array
     */
    protected function getDefaultOptions()
    {
        if (!$this->defaultColumn) {
            $this->defaultColumn = new Column('_template_', Type::getType(Type::STRING));
        }
        if (!$this->defaultColumnOptions) {
            foreach ($this->optionNames as $optionName) {
                $this->defaultColumnOptions[$optionName] = $this->getColumnOption($this->defaultColumn, $optionName);
            }
        }

        return $this->defaultColumnOptions;
    }
}
