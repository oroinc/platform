<?php

namespace Oro\Bundle\OrganizationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\OrganizationBundle\Controller\Api\Rest\BusinessUnitController;
use Oro\Bundle\OrganizationBundle\DependencyInjection\OroOrganizationExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroOrganizationExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroOrganizationExtension());

        $expectedDefinitions = [
            BusinessUnitController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
