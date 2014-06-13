<?php

namespace Oro\Bundle\ImportExportBundle\Reader;

use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextRegistry;
use Oro\Bundle\ImportExportBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureRegistry;

class TemplateFixtureReader extends IteratorBasedReader
{
    /**
     * @var TemplateFixtureRegistry
     */
    protected $fixtureRegistry;

    /**
     * @var TemplateFixtureInterface
     */
    protected $fixture;

    /**
     * @param ContextRegistry $contextRegistry
     * @param TemplateFixtureRegistry $fixtureRegistry
     */
    public function __construct(ContextRegistry $contextRegistry, TemplateFixtureRegistry $fixtureRegistry)
    {
        parent::__construct($contextRegistry);

        $this->fixtureRegistry = $fixtureRegistry;
    }

    /**
     * {@inheritdoc}
     */
    protected function initializeFromContext(ContextInterface $context)
    {
        if ($context->hasOption('entityName')) {
            $this->fixture = $this->fixtureRegistry->getEntityFixture($context->getOption('entityName'));
            $this->setSourceIterator($this->fixture->getData());
        } else {
            throw new InvalidConfigurationException(
                'Configuration of fixture reader must contain "entityName".'
            );
        }
    }
}
