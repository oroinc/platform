<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\DataAuditBundle\Controller\Api\Rest\AuditController;
use Oro\Bundle\DataAuditBundle\DependencyInjection\OroDataAuditExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroDataAuditExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroDataAuditExtension());

        $expectedDefinitions = [
            AuditController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
