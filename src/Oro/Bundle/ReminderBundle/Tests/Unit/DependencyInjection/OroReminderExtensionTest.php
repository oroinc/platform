<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ReminderBundle\DependencyInjection\OroReminderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroReminderExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OroReminderExtension
     */
    private $extension;

    /**
     * @var ContainerBuilder
     */
    private $container;

    protected function setUp()
    {
        $this->container = new ContainerBuilder();
        $this->extension = new OroReminderExtension();
    }

    public function testLoad()
    {
        $this->extension->load(array(), $this->container);
    }
}
