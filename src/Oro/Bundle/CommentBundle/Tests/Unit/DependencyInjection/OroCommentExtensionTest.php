<?php

namespace Oro\Bundle\CommentBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\CommentBundle\DependencyInjection\OroCommentExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroCommentExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();

        $extension = new OroCommentExtension();
        $extension->load([], $container);

        self::assertNotEmpty($container->getDefinitions());
    }
}
