<?php

namespace Oro\Bundle\ReminderBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\ReminderBundle\DependencyInjection\OroReminderExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroReminderExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroReminderExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
