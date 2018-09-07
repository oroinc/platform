<?php

namespace Oro\Bundle\DataGridBundle\Tests\Unit\DependencyInjection\CompilerPass;

use Oro\Bundle\DataGridBundle\DependencyInjection\CompilerPass\SelectedFieldsProvidersPass;
use Oro\Component\DependencyInjection\Tests\Unit\Compiler\TaggedServicesCompilerPassCase;

class SelectedFieldsProviderPassTest extends TaggedServicesCompilerPassCase
{
    public function testProcess(): void
    {
        $this->assertTaggedServicesRegistered(
            new SelectedFieldsProvidersPass(),
            'oro_datagrid.provider.selected_fields',
            'oro_datagrid.selected_fields_provider',
            'addSelectedFieldsProvider'
        );
    }
}
