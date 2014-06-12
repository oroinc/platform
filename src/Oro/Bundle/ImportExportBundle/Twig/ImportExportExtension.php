<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry;

class ImportExportExtension extends \Twig_Extension
{
    const NAME = 'oro_importexport';

    /**
     * @var TemplateFixtureRegistry
     */
    protected $templateFixtureRegistry;

    /**
     * @param TemplateFixtureRegistry $templateFixtureRegistry
     */
    public function __construct(TemplateFixtureRegistry $templateFixtureRegistry)
    {
        $this->templateFixtureRegistry = $templateFixtureRegistry;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('has_export_template', array($this, 'hasTemplateFixture')),
        );
    }

    /**
     * @param string $entityName
     * @return bool
     */
    public function hasTemplateFixture($entityName)
    {
        return $this->templateFixtureRegistry->hasEntityFixture($entityName);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
