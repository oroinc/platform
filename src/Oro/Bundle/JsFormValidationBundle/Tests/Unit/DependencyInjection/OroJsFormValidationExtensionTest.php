<?php

namespace Oro\Bundle\JsFormValidationBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\JsFormValidationBundle\DependencyInjection\OroJsFormValidationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroJsFormValidationExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testLoad()
    {
        $container = new ContainerBuilder();
        $extension = new OroJsFormValidationExtension();

        $configs = array(array());
        $extension->load($configs, $container);

        $this->assertTrue(
            $container->hasDefinition('jsfv.generator'),
            'The jsfv.generator is overridden'
        );
    }
}
