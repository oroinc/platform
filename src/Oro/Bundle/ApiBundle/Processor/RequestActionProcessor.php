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
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /**
     * @param ProcessorBagInterface $processorBag
     * @param string                $action
     * @param ConfigProvider        $configProvider
     * @param MetadataProvider      $metadataProvider
     */
    public function __construct(
        ProcessorBagInterface $processorBag,
        $action,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        parent::__construct($processorBag, $action);

        $this->configProvider   = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }
}
