<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit;

use Oro\Bundle\ReminderBundle\OroReminderBundle;

class OroReminderBundleTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        new OroReminderBundle();
    }

    public function testBuild()
    {
        $containerBuilder = $this
            ->getMockBuilder('Symfony\\Component\\DependencyInjection\\ContainerBuilder')
            ->disableOriginalConstructor()
            ->setMethods(array('addCompilerPass'))
            ->getMock();

        $containerBuilder
            ->expects($this->atLeastOnce())
            ->method('addCompilerPass');

        $bundle = new OroReminderBundle();
        $bundle->build($containerBuilder);
    }
}
