<?php

namespace Oro\Bundle\GaufretteBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\DependencyInjection\Compiler\SetGaufretteFilesystemsLazyPass;
use Oro\Bundle\GaufretteBundle\FilesystemMap;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Reference;

class SetGaufretteFilesystemsLazyPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    /** @var SetGaufretteFilesystemsLazyPass */
    private $compiler;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->compiler = new SetGaufretteFilesystemsLazyPass();
    }

    public function testNoFilesystemMapService()
    {
        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage('You have requested a non-existent service "knp_gaufrette.filesystem_map".');

        $this->compiler->process($this->container);
    }

    public function testNoFirstArgumentInFilesystemMapService()
    {
        $this->container->register('knp_gaufrette.filesystem_map', FilesystemMap::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'It is expected that the argument "0" of the "knp_gaufrette.filesystem_map" service is an array.'
        );

        $this->compiler->process($this->container);
    }

    public function testFirstArgumentInFilesystemMapServiceIsNotArray()
    {
        $this->container->register('knp_gaufrette.filesystem_map', FilesystemMap::class)
            ->addArgument(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'It is expected that the argument "0" of the "knp_gaufrette.filesystem_map" service is an array.'
        );

        $this->compiler->process($this->container);
    }

    public function testFirstArgumentInFilesystemMapServiceIsUnexpectedArray()
    {
        $this->container->register('knp_gaufrette.filesystem_map', FilesystemMap::class)
            ->addArgument(['key' => 'val']);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'It is expected that each element of the Gaufrette filesystem map is an instance of '
            . '"Symfony\Component\DependencyInjection\Reference", got "string" for the "key" filesystem.'
        );

        $this->compiler->process($this->container);
    }

    public function testShouldMarkAllFilesystemsAsLazy()
    {
        $this->container->register('knp_gaufrette.filesystem_map', FilesystemMap::class)
            ->addArgument([
                'filesystem1' => new Reference('filesystem1_service'),
                'filesystem2' => new Reference('filesystem2_service')
            ]);
        $this->container->register('filesystem1_service');
        $this->container->register('filesystem2_service');

        $this->compiler->process($this->container);

        self::assertTrue($this->container->getDefinition('filesystem1_service')->isLazy());
        self::assertTrue($this->container->getDefinition('filesystem2_service')->isLazy());
    }
}
