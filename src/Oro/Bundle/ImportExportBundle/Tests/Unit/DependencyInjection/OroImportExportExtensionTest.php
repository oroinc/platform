<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ImportExportBundle\DependencyInjection\OroImportExportExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroImportExportExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroImportExportExtension());

        $expectedDefinitions = [
            'oro_importexport.configuration.registry',
            'oro_importexport.twig_extension.get_import_export_configuration',
            'oro_importexport.twig_extension.basename',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
