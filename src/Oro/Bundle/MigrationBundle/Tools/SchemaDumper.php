<?php

namespace Oro\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

class SchemaDumper extends AbstractVisitor
{
    const SCHEMA_TEMPLATE = 'OroMigrationBundle::schema-template.php.twig';
    const DEFAULT_CLASS_NAME = 'AllMigration';
    const DEFAULT_VERSION = 'v1_0';

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @param array|null $allowedTables
     * @param string|null $namespace
     * @param string $className
     * @param string $version
     * @param array|null $extendedOptions
     * @return string
     */
    public function dump(
        array $allowedTables = null,
        $namespace = null,
        $className = self::DEFAULT_CLASS_NAME,
        $version = self::DEFAULT_VERSION,
        array $extendedOptions = null
    ) {
        $content = $this->twig->render(
            self::SCHEMA_TEMPLATE,
            [
                'schema' => $this->schema,
                'allowedTables' => $allowedTables,
                'namespace' => $this->getMigrationNamespace($namespace),
                'className' => $className,
                'version' => $version,
                'extendedOptions' => $extendedOptions
            ]
        );

        return $content;
    }

    protected function getMigrationNamespace($namespace)
    {
        if ($namespace) {
            $namespace = str_replace('\\Entity', '', $namespace);
        }

        return $namespace;
    }
}
