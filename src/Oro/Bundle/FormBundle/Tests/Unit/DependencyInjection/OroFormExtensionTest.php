<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\FormBundle\DependencyInjection\OroFormExtension;
use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;

class OroFormExtensionTest extends ExtensionTestCase
{
    public function testLoad()
    {
        $this->loadExtension(new OroFormExtension());

        $expectedDefinitions = [
            'oro_form.type.encoded_placeholder_password',
        ];

        $this->assertDefinitionsLoaded($expectedDefinitions);
    }
}
