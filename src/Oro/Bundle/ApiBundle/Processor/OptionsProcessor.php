<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Processor\Options\OptionsContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

/**
 * The main processor for "options" action.
 */
class OptionsProcessor extends NormalizeResultActionProcessor
{
    private ConfigProvider $configProvider;
    private MetadataProvider $metadataProvider;

    public function __construct(
        ProcessorBagInterface $processorBag,
        string $action,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        parent::__construct($processorBag, $action);
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function createContextObject(): OptionsContext
    {
        return new OptionsContext($this->configProvider, $this->metadataProvider);
    }
}
