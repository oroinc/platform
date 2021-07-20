<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit;

use Oro\Bundle\DistributionBundle\OroKernel;
use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\BundleStub;
use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\OroKernelStub;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OroKernelTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var OroKernel|OroKernelStub
     */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->kernel = new OroKernelStub('env', false);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        $this->removeDir($this->kernel->getCacheDir());
        $this->removeDir($this->kernel->getLogDir());
    }

    private function removeDir(string $dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getRealPath());
            } else {
                unlink($fileInfo->getRealPath());
            }
        }

        rmdir($dir);
    }

    /**
     * @dataProvider bundleList
     */
    public function testCompareBundles($bundles, $expects)
    {
        uasort($bundles, [$this->kernel, 'compareBundles']);
        $id = 0;
        foreach ($bundles as $bundleData) {
            $this->assertEquals($expects[$id], $bundleData['name']);
            $id++;
        }
    }

    public function bundleList(): array
    {
        return [
            [
                [
                    ['name' => 'OroCallBundle', 'priority' => 30],
                    ['name' => 'OroTestBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle',
                    'OroTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroTestBundle', 'priority' => 30],
                    ['name' => 'OroCallBundle', 'priority' => 30],
                ],
                [
                    'OroCallBundle',
                    'OroTestBundle'
                ]
            ],
            [
                [
                    ['name' => 'OroSecondBundle', 'priority' => 30],
                    ['name' => 'OroFirstBundle', 'priority' => 20],

                ],
                [
                    'OroFirstBundle',
                    'OroSecondBundle'
                ]
            ],
            [
                [
                    ['name' => 'AcmeLastBundle', 'priority' => 100],
                    ['name' => 'OroSomeBundle', 'priority' => 30],
                    ['name' => 'AcmeTestBundle', 'priority' => 1],
                    ['name' => 'OroAnotherBundle', 'priority' => 30],
                    ['name' => 'AcmeDemoBundle', 'priority' => 100],
                ],
                [
                    'AcmeTestBundle',
                    'OroAnotherBundle',
                    'OroSomeBundle',
                    'AcmeDemoBundle',
                    'AcmeLastBundle',
                ]
            ]
        ];
    }

    /**
     * @dataProvider bundlesDataProvider
     */
    public function testRegisterBundles(array $bundles)
    {
        $this->assertEquals(
            $bundles,
            $this->kernel->registerBundles()
        );
    }

    /**
     * @return array
     */
    public function bundlesDataProvider()
    {
        return [
            [
                [
                    new BundleStub('Acme\Bundle\TestBundle\AcmeSimplifiedBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeDuplicateBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeFirstRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeSecondRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeThirdRegisteredBundle'),
                ],
            ]
        ];
    }

    public function testBootDeploymentWitoutParameters()
    {
        $this->kernel->setAppDir('application/app1-without-parameters');
        $this->kernel->boot();

        /* @var $container ContainerInterface */
        $container = $this->kernel->getContainer();
        $this->assertFalse($container->hasParameter('deployment_type'));
        $this->assertEquals('configParam1GlobalValue', $container->getParameter('configParam1'));
    }

    public function testBootDeploymentWithEmptyDeploymetType()
    {
        $this->kernel->setAppDir('application/app2-without-deployment-type');
        $this->kernel->boot();

        /* @var $container ContainerInterface */
        $container = $this->kernel->getContainer();
        $this->assertNull($container->getParameter('deployment_type'));
        $this->assertEquals('configParam1GlobalValue', $container->getParameter('configParam1'));
    }

    public function testBootDeploymentWithoutDeploymentFile()
    {
        $this->kernel->setAppDir('application/app3-without-deployment-config');
        $this->kernel->boot();

        /* @var $container ContainerInterface */
        $container = $this->kernel->getContainer();
        $this->assertFalse($container->hasParameter('deployment_type'));
    }

    public function testBootDeploymentWithLocalConfig()
    {
        $this->kernel->setAppDir('application/app4-with-deployment-config');
        $this->kernel->boot();

        /* @var $container ContainerInterface */
        $container = $this->kernel->getContainer();
        $this->assertEquals('local', $container->getParameter('deployment_type'));
        $this->assertEquals('configParam1DeploymentValue', $container->getParameter('configParam1'));
    }
}
