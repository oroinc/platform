<?php

namespace Oro\Bundle\TagBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TagBundle\Controller\Api\Rest\TagController;
use Oro\Bundle\TagBundle\Controller\Api\Rest\TaggableController;
use Oro\Bundle\TagBundle\Controller\Api\Rest\TaxonomyController;
use Oro\Bundle\TagBundle\DependencyInjection\OroTagExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroTagExtensionTest extends ExtensionTestCase
{
    public function testLoad(): void
    {
        $this->loadExtension(new OroTagExtension());

        $expectedDefinitions = [
            TagController::class,
            TaggableController::class,
            TaxonomyController::class,
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
