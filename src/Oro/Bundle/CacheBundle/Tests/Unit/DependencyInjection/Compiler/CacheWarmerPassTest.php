<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\CacheBundle\DependencyInjection\Compiler\CacheWarmerPass;
use Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TestDumper1;
use Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TestDumper2;
use Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TestWarmer1;
use Oro\Bundle\CacheBundle\Tests\Unit\DependencyInjection\Compiler\Stub\TestWarmer2;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class CacheWarmerPassTest extends \PHPUnit\Framework\TestCase
{
    /** @var CacheWarmerPass */
    protected $compilerPass;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->compilerPass = new CacheWarmerPass();
    }

    /**
     * @dataProvider classesDataProvider
     *
     * @param string $dumperClass
     * @param string $warmerClass
     * @param bool $isValid
     */
    public function testProcess($dumperClass, $warmerClass, $isValid = false)
    {
        $definition1 = new Definition($dumperClass);
        $definition2 = new Definition($warmerClass);
        $definition2->addTag(CacheWarmerPass::PROVIDER_TAG, ['dumper' => 'dumper1']);

        if (false === $isValid) {
            $this->expectException('\InvalidArgumentException');
        }

        /** @var \PHPUnit\Framework\MockObject\MockObject|Definition $service */
        $service = $this->createMock(Definition::class);
        $service->expects(true === $isValid ? $this->once() : $this->never())->method('addMethodCall');

        $container = new ContainerBuilder(new ParameterBag(['kernel.debug' => true]));
        $container->setDefinition('dumper1', $definition1);
        $container->setDefinition('test_def', $definition2);
        $container->setDefinition(CacheWarmerPass::SERVICE_ID, $service);

        $this->compilerPass->process($container);
    }

    /**
     * @return \Generator
     */
    public function classesDataProvider()
    {
        yield 'is valid' => [
            'dumperClass' => TestDumper1::class,
            'warmerClass' => TestWarmer1::class,
            'isValid' => true
        ];

        yield 'dumper not implements interface' => [
            'dumperClass' => TestDumper2::class,
            'warmerClass' => TestWarmer1::class
        ];

        yield 'warmer not implements interface' => [
            'dumperClass' => TestDumper1::class,
            'warmerClass' => TestWarmer2::class
        ];

        yield 'class not exist' => [
            'dumperClass' => 'TestNotExistClass',
            'warmerClass' => TestWarmer1::class
        ];
    }
}
