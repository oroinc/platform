<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\ReminderBundle\DependencyInjection\OroReminderExtension;

class OroReminderExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var OroReminderExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    public function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroReminderExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
