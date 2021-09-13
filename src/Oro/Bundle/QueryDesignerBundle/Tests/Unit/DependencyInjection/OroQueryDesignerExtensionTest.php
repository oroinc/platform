<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\QueryDesignerBundle\Controller\Api\Rest\QueryDesignerEntityController;
use Oro\Bundle\QueryDesignerBundle\DependencyInjection\OroQueryDesignerExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroQueryDesignerExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroQueryDesignerExtension());

        $expectedDefinitions = [
            QueryDesignerEntityController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
