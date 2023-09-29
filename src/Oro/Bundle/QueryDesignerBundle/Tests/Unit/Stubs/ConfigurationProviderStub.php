<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\Stubs;

use Oro\Bundle\QueryDesignerBundle\QueryDesigner\ConfigurationProvider;

/**
 * Configuration provider stub for testing purposes
 */
class ConfigurationProviderStub extends ConfigurationProvider
{
    protected function getAppConfigPath(): string
    {
        return '/../../Config/oro/query_designer';
    }
}
