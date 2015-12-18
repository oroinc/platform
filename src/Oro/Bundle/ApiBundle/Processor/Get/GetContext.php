<?php

namespace Oro\Bundle\ApiBundle\Processor\Get;

use Oro\Bundle\ApiBundle\Config\FiltersConfigExtra;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

class GetContext extends SingleItemContext
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct($configProvider, $metadataProvider);

        $this->setConfigExtras([new FiltersConfigExtra()]);
    }
}
