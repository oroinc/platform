<?php

namespace Oro\Bundle\MigrationBundle\Tools;

use Doctrine\DBAL\Platforms\AbstractPlatform;
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
     * @var SchemaDumperTwigExtension
     */
    protected $twigExtension;

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

        $this->ensureTwigExtensionCreated();
        $this->twig->addExtension($this->twigExtension);
    }

    public function setPlatform(AbstractPlatform $platform)
    {
        $this->ensureTwigExtensionCreated();
        $this->twigExtension->setPlatform($platform);
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

    protected function ensureTwigExtensionCreated()
    {
        if (!$this->twigExtension) {
            $this->twigExtension = new SchemaDumperTwigExtension();
        }
    }
}
