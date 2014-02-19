<?php

namespace Oro\Bundle\InstallerBundle\Migrations\Visitor;


use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\Visitor\AbstractVisitor;

class SchemaDumper extends AbstractVisitor
{
    const SCHEMA_TEMPLATE = 'OroInstallerBundle::schema-template.php.twig';

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * {@inheritdoc}
     */
    public function acceptSchema(Schema $schema)
    {
        $this->schema = $schema;
    }

    public function setTwig(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }


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
