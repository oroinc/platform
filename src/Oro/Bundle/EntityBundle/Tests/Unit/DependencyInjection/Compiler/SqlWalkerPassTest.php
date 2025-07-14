<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler;

use Doctrine\ORM\Query;
use Oro\Bundle\EntityBundle\DependencyInjection\Compiler\SqlWalkerPass;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler\Stub\AstWalkerStub;
use Oro\Bundle\EntityBundle\Tests\Unit\DependencyInjection\Compiler\Stub\OutputResultModifierStub;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputAstWalkerInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\OutputResultModifierInterface;
use Oro\Component\DoctrineUtils\ORM\Walker\SqlWalker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class SqlWalkerPassTest extends TestCase
{
    private SqlWalkerPass $compiler;

    #[\Override]
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

        $astWalker = AstWalkerStub::class;
        $container->register('service2', $astWalker)
            ->addTag('oro_entity.sql_walker');

        $outputResultModifier = OutputResultModifierStub::class;
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
