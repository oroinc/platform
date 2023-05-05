<?php

namespace Oro\Bundle\NoteBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\NoteBundle\DependencyInjection\OroNoteExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroNoteExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroNoteExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
