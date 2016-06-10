<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ProcessorBag;

use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;
use Oro\Bundle\ApiBundle\Processor\Update\UpdateContext;

class UpdateProcessor extends RequestActionProcessor
{
    /** @var ConfigProvider */
    protected $configProvider;

    /** @var MetadataProvider */
    protected $metadataProvider;

    /**
     * @param ProcessorBag     $processorBag
     * @param string           $action
     * @param ConfigProvider   $configProvider
     * @param MetadataProvider $metadataProvider
     */
    public function __construct(
        ProcessorBag $processorBag,
        $action,
        ConfigProvider $configProvider,
        MetadataProvider $metadataProvider
    ) {
        parent::__construct($processorBag, $action);

        $this->configProvider   = $configProvider;
        $this->metadataProvider = $metadataProvider;
    }

    /**
     * {@inheritdoc}
     */
    protected function createContextObject()
    {
        return new UpdateContext($this->configProvider, $this->metadataProvider);
    }
}
