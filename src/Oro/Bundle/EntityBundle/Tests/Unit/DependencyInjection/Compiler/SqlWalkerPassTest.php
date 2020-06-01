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
    /**
     * @var SqlWalkerPass
     */
    private $pass;

    protected function setUp(): void
    {
        $this->pass = new SqlWalkerPass();
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $configDefinition = new Definition();
        $container->setDefinition('doctrine.orm.configuration', $configDefinition);

        $container->register('service1', Stub\AstWalkerStub::class)
            ->addTag(SqlWalkerPass::TAG_NAME);
        $container->register('service2', Stub\OutputResultModifierStub::class)
            ->addTag(SqlWalkerPass::TAG_NAME);

        $this->pass->process($container);

        $methodCalls = $configDefinition->getMethodCalls();
        $this->assertCount(3, $methodCalls);
        $this->assertContains(
            [
                'setDefaultQueryHint',
                [Query::HINT_CUSTOM_OUTPUT_WALKER, SqlWalker::class]
            ],
            $methodCalls
        );
        $this->assertContains(
            [
                'setDefaultQueryHint',
                [OutputAstWalkerInterface::HINT_AST_WALKERS, [Stub\AstWalkerStub::class]]
            ],
            $methodCalls
        );
        $this->assertContains(
            [
                'setDefaultQueryHint',
                [OutputResultModifierInterface::HINT_RESULT_MODIFIERS, [Stub\OutputResultModifierStub::class]]
            ],
            $methodCalls
        );
    }
}
