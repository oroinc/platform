<?php

namespace Oro\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

class SchemaDumper extends AbstractVisitor
{
    const SCHEMA_TEMPLATE = 'OroMigrationBundle::schema-template.php.twig';

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
     * @return string
     */
    public function dump()
    {
        $content = $this->twig->render(
            self::SCHEMA_TEMPLATE,
            [
                'schema' => $this->schema,
            ]
        );

        return $content;
    }
}
