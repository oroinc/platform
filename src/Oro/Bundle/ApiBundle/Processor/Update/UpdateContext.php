<?php

namespace Oro\Bundle\ApiBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Processor\FormContext;
use Oro\Bundle\ApiBundle\Processor\FormContextTrait;
use Oro\Bundle\ApiBundle\Processor\SingleItemContext;
use Oro\Bundle\ApiBundle\Provider\ConfigProvider;
use Oro\Bundle\ApiBundle\Provider\MetadataProvider;

/**
 * The execution context for processors for "update" action.
 */
class UpdateContext extends SingleItemContext implements FormContext
{
    use FormContextTrait;

    public function __construct(ConfigProvider $configProvider, MetadataProvider $metadataProvider)
    {
        parent::__construct($configProvider, $metadataProvider);
        $this->setExisting(true);
    }
}
