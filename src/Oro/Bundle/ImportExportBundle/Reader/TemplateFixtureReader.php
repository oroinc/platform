<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

/**
 * Reads template fixture data for export template generation.
 *
 * This reader retrieves template fixture entities from the template manager based on
 * the entity name specified in the context. It provides sample data that represents
 * the structure and format of entities, which is used to generate export templates
 * that users can download and use as a reference for import files.
 */
class TemplateFixtureReader extends IteratorBasedReader
{
    /**
     * @var TemplateManager
     */
    protected $templateManager;

    /**
     * @var TemplateFixtureInterface
     */
    protected $fixture;

    public function __construct(ContextRegistry $contextRegistry, TemplateManager $templateManager)
    {
        parent::__construct($contextRegistry);

        $this->templateManager = $templateManager;
    }

    #[\Override]
    protected function initializeFromContext(ContextInterface $context)
    {
        if (!$context->hasOption('entityName')) {
            throw new InvalidConfigurationException(
                'Configuration of fixture reader must contain "entityName".'
            );
        }

        $this->fixture = $this->templateManager->getEntityFixture($context->getOption('entityName'));
        $this->setSourceIterator($this->fixture->getData());
    }
}
