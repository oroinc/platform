<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\AddressBundle\Controller\Api\Rest as Api;
use Oro\Bundle\AddressBundle\DependencyInjection\OroAddressExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroAddressExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroAddressExtension());

        $expectedDefinitions = [
            Api\AddressTypeController::class,
            Api\CountryController::class,
            Api\CountryRegionsController::class,
            Api\RegionController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
