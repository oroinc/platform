<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class EntityFallbackFieldsStoragePassTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContainerBuilder */
    private $container;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->container->register('oro_locale.storage.entity_fallback_fields_storage')
            ->addArgument([]);
    }

    private function runCompiler(array $classes): void
    {
        $compiler = new EntityFallbackFieldsStoragePass($classes);
        $compiler->process($this->container);
    }

    public function testProcess(): void
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
            $this->container->getDefinition('oro_locale.storage.entity_fallback_fields_storage')->getArgument(0)
        );
    }
}
