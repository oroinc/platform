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
    /** @var ConfigProvider */
    private $configProvider;

    /** @var MetadataProvider */
    private $metadataProvider;

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
        $this->configProvider = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new OptionsContext($this->configProvider, $this->metadataProvider);
    }
}
