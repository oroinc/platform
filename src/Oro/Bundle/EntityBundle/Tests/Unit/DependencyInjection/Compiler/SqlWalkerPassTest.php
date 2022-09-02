<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\SqlWalkerPass;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputAstWalkerInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputResultModifierInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\SqlWalker;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SqlWalkerPassTest extends \PHPUnit\Framework\TestCase
{
    private SqlWalkerPass $compiler;

    protected function setUp(): void
    {
        $this->compiler = new SqlWalkerPass();
    }

    public function testProcess(): void
    {
        $container = new ContainerBuilder();
        $configDefinition = new Definition();
        $container->setDefinition('doctrine.orm.configuration', $configDefinition);

        $astWalkerWithLowerPriority = get_class($this->createMock(OutputAstWalkerInterface::class));
        $container->register('service1', $astWalkerWithLowerPriority)
            ->addTag('oro_entity.sql_walker', ['priority' => -42]);

        $astWalker = Stub\AstWalkerStub::class;
        $container->register('service2', $astWalker)
            ->addTag('oro_entity.sql_walker');

        $outputResultModifier = Stub\OutputResultModifierStub::class;
        $container->register('service3', $outputResultModifier)
            ->addTag('oro_entity.sql_walker');

        $outputResultModifierWithHigherPriority = get_class($this->createMock(OutputResultModifierInterface::class));
        $container->register('service4', $outputResultModifierWithHigherPriority)
            ->addTag('oro_entity.sql_walker', ['priority' => 42]);

        $this->compiler->process($container);

        $methodCalls = $configDefinition->getMethodCalls();
        self::assertCount(3, $methodCalls);
        self::assertContains(
            [
                'setDefaultQueryHint',
                [Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class],
            ],
            $methodCalls
        );
        self::assertContains(
            [
                'setDefaultQueryHint',
                [OutputAstWalkerInterface::HINT_AST_WALKERS, [$astWalker, $astWalkerWithLowerPriority]],
            ],
            $methodCalls
        );
        self::assertContains(
            [
                'setDefaultQueryHint',
                [
                    OutputResultModifierInterface::HINT_RESULT_MODIFIERS,
                    [$outputResultModifierWithHigherPriority, $outputResultModifier],
                ],
            ],
            $methodCalls
        );
    }
}
