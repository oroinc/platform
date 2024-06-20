<?php

namespace Oro\Bundle\QueryDesignerBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\QueryDesignerBundle\DependencyInjection\Compiler\RegisterCollapsedAssociationsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterCollapsedAssociationsPassTest extends \PHPUnit\Framework\TestCase
{
    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $container->getParameterBag()->set('oro_query_designer.collapsed_associations', [
            'Test\Entity1' => ['virtual_fields' => ['id'], 'search_fields' => ['name']],
            'Test\Entity2' => ['virtual_fields' => ['code'], 'search_fields' => ['label']]
        ]);
        $dictionaryVirtualFieldProvider = $container->register('oro_entity.virtual_field_provider.dictionary');
        $dictionaryEntityDataProvider = $container->register('oro_entity.dictionary_entity_data_provider');

        $compiler = new RegisterCollapsedAssociationsPass();
        $compiler->process($container);

        self::assertFalse($container->getParameterBag()->has('oro_query_designer.collapsed_associations'));
        self::assertEquals(
            [
                ['registerDictionary', ['Test\Entity1', ['id']]],
                ['registerDictionary', ['Test\Entity2', ['code']]]
            ],
            $dictionaryVirtualFieldProvider->getMethodCalls()
        );
        self::assertEquals(
            [
                ['registerDictionary', ['Test\Entity1', ['name']]],
                ['registerDictionary', ['Test\Entity2', ['label']]]
            ],
            $dictionaryEntityDataProvider->getMethodCalls()
        );
    }
}
