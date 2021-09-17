<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\SegmentBundle\Controller\Api\Rest\SegmentController;
use Oro\Bundle\SegmentBundle\DependencyInjection\OroSegmentExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroSegmentExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroSegmentExtension());

        $expectedDefinitions = [
            SegmentController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
