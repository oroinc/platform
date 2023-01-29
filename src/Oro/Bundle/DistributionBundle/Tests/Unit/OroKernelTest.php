<?php

namespace Oro\Bundle\DistributionBundle\Tests\Unit;

use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\BundleStub;
use Oro\Bundle\DistributionBundle\Tests\Unit\Stub\OroKernelStub;

class OroKernelTest extends \PHPUnit\Framework\TestCase
{
    private OroKernelStub $kernel;

    protected function setUp(): void
    {
        $this->kernel = new OroKernelStub('env', false);
    }

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
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
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
    public function testCompareBundles(array $bundles, array $expects)
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
                    ['name' => 'AcmeTestBundle'],
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

    public function bundlesDataProvider(): array
    {
        return [
            [
                [
                    new BundleStub('Acme\Bundle\TestBundle\AcmeSimplifiedBundle'),
                    new BundleStub(BundleStub::class), // installed optional bundle
                    new BundleStub('Acme\Bundle\TestBundle\AcmeDuplicateBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeFirstRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeSecondRegisteredBundle'),
                    new BundleStub('Acme\Bundle\TestBundle\AcmeThirdRegisteredBundle'),
                ],
            ]
        ];
    }
}
