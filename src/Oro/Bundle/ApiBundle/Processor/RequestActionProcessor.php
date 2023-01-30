<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Component\ChainProcessor\ProcessorBagInterface;

/**
 * The base processor for API actions that works with defined type of a resource.
 */
class RequestActionProcessor extends NormalizeResultActionProcessor
{
    protected ConfigProvider $configProvider;
    protected MetadataProvider $metadataProvider;

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
}
