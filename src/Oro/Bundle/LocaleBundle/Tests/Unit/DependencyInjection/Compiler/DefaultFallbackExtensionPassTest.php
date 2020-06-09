<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultFallbackExtensionPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->register('oro_locale.entity_generator.extension')
            ->addArgument([]);
    }

    /**
     * @param array $classes
     *
     * @return DefaultFallbackExtensionPass
     */
    private function runCompiler(array $classes)
    {
        $compiler = new DefaultFallbackExtensionPass($classes);
        $compiler->process($this->container);
    }

    public function testProcess()
    {
        $this->runCompiler([]);
        $this->runCompiler(['Test\Entity1' => ['name' => 'names']]);
        $this->runCompiler(['Test\Entity2' => ['name' => 'names']]);
        $this->runCompiler(['Test\Entity2' => ['description' => 'descriptions']]);
        $this->runCompiler(['Test\Entity2' => []]);

        $this->assertEquals(
            [
                'Test\Entity1' => ['name' => 'names'],
                'Test\Entity2' => ['name' => 'names', 'description' => 'descriptions']
            ],
            $this->container->getDefinition('oro_locale.entity_generator.extension')->getArgument(0)
        );
    }
}
