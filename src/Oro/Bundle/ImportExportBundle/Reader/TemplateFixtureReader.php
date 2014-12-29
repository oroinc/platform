<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;

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

    /**
     * @param ContextRegistry $contextRegistry
     * @param TemplateManager $templateManager
     */
    public function __construct(ContextRegistry $contextRegistry, TemplateManager $templateManager)
    {
        parent::__construct($contextRegistry);

        $this->templateManager = $templateManager;
    }

    /**
     * {@inheritdoc}
     */
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
