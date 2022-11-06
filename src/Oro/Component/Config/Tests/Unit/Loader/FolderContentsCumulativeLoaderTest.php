<?php

namespace Oro\Component\Config\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResource;
use Oro\Component\Config\CumulativeResourceInfo;
use Oro\Component\Config\Loader\ByFileNameMatcher;
use Oro\Component\Config\Loader\CumulativeResourceLoaderCollection;
use Oro\Component\Config\Loader\FolderContentCumulativeLoader;
use Oro\Component\Config\Tests\Unit\Fixtures\Bundle\TestBundle1\TestBundle1;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Filesystem\Filesystem;

class FolderContentsCumulativeLoaderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $rootDir = '';

    private string $bundleDir = '';

    private function getRootDir(): string
    {
        if (!$this->rootDir) {
            $this->rootDir = $this->copyToTempDir('test_data', realpath(__DIR__ . '/../Fixtures'));
        }

        return $this->rootDir;
    }

    private function getBundleDir(): string
    {
        if (!$this->bundleDir) {
            $this->bundleDir = $this->getRootDir() . $this->getPath('/Bundle/TestBundle1');
        }

        return $this->bundleDir;
    }

    private function getPath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    private function getLoader(
        string $relativeFolderPath,
        array $fileNamePatterns,
        int $maxNestingLevel = -1,
        bool $plainResultStructure = true
    ): FolderContentCumulativeLoader {
        return new FolderContentCumulativeLoader(
            $relativeFolderPath,
            $maxNestingLevel,
            $plainResultStructure,
            new ByFileNameMatcher($fileNamePatterns)
        );
    }

    private function getLastChangeTime(CumulativeResource $resource, string $bundleClass): int
    {
        $lastChangeTime = 0;
        foreach ($resource->getFound($bundleClass) as $file) {
            $fileTime = filemtime($file);
            if ($fileTime > $lastChangeTime) {
                $lastChangeTime = $fileTime;
            }
        }

        return $lastChangeTime;
    }

    public function testResourceName()
    {
        $loader = $this->getLoader('Resources/folder_to_track/', []);

        $this->assertSame('Folder content: Resources/folder_to_track/', $loader->getResource());
    }

    public function testSerialization()
    {
        $fileMatcher = new ByFileNameMatcher(['/\.yml$/', '/\.xml$/']);
        $loader = new FolderContentCumulativeLoader(
            'Resources/folder_to_track/',
            3,
            false,
            $fileMatcher
        );

        $unserialized = unserialize(serialize($loader));
        $this->assertEquals($loader, $unserialized);
    }

    /**
     * @dataProvider loadFlatModeDataProvider
     */
    public function testLoadInFlatMode(
        ?array $expectedResult,
        array $expectedRegisteredResources,
        string $path,
        int $nestingLevel = -1,
        array $fileNamePatterns = ['/\.yml$/', '/\.xml$/']
    ) {
        $loader = $this->getLoader($path, $fileNamePatterns, $nestingLevel);

        $bundleClass = TestBundle1::class;
        $bundleDir = $this->getBundleDir();

        /** @var CumulativeResourceInfo $result */
        $result = $loader->load($bundleClass, $bundleDir);
        if (!is_array($expectedResult)) {
            $this->assertSame($expectedResult, $result);
        } else {
            $this->assertInstanceOf(CumulativeResourceInfo::class, $result);
            $this->assertSame($bundleClass, $result->bundleClass);
            $this->assertSame('Folder content: ' . $path, $result->name);

            $realDir = realpath($this->getPath($bundleDir . '/' . $path));
            $this->assertSame($realDir, $result->path);

            sort($result->data);
            $this->assertSame($expectedResult, $result->data, 'expected result');
        }

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $foundResources = $resource->getFound($bundleClass);
        sort($foundResources);
        $this->assertEquals($expectedRegisteredResources, $foundResources, 'expected registered resources');
    }

    public function loadFlatModeDataProvider(): array
    {
        $bundleDir = $this->getBundleDir();

        return [
            'empty dir, nothing to load'                                      => [
                'expectedResult'              => null,
                'expectedRegisteredResources' => [],
                'path'                        => 'unknown dir/'
            ],
            'loading contents'                                                => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => -1,
                'fileExtensions'              => []
            ],
            'loading contents filtered by file extensions'                    => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => -1
            ],
            'loading contents limit nesting level'                            => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => 1
            ],
            'loading contents limit nesting level that takes all files exist' => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => 2
            ]
        ];
    }

    /**
     * @dataProvider loadFlatModeDataProviderWithAppRootDirectory
     */
    public function testLoadInFlatModeWithAppRootDirectory(
        ?array $expectedResult,
        array $expectedRegisteredResources,
        string $path,
        int $nestingLevel = -1,
        array $fileNamePatterns = ['/\.yml$/', '/\.xml$/']
    ) {
        $loader = $this->getLoader($path, $fileNamePatterns, $nestingLevel);

        $bundle = new TestBundle1();
        $bundleClass = get_class($bundle);
        $bundleDir = dirname((new \ReflectionClass($bundle))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');
        $bundleAppDir = $appRootDir . '/Resources/TestBundle1';

        /** @var CumulativeResourceInfo $result */
        $result = $loader->load($bundleClass, $bundleDir, $bundleAppDir);
        if (!is_array($expectedResult)) {
            $this->assertSame($expectedResult, $result);
        } else {
            $this->assertInstanceOf(CumulativeResourceInfo::class, $result);
            $this->assertSame($bundleClass, $result->bundleClass);
            $this->assertSame('Folder content: ' . $path, $result->name);

            $realDir = realpath($this->getPath($bundleDir . '/' . $path));
            $this->assertSame($realDir, $result->path);

            sort($result->data);
            sort($expectedResult);
            $this->assertSame($expectedResult, $result->data, 'expected result');
        }

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);

        $foundResources = $resource->getFound($bundleClass);
        sort($foundResources);
        sort($expectedRegisteredResources);
        $this->assertEquals($expectedRegisteredResources, $foundResources, 'expected registered resources');
    }

    public function loadFlatModeDataProviderWithAppRootDirectory(): array
    {
        $bundleDir = dirname((new \ReflectionClass(new TestBundle1()))->getFileName());
        $appRootDir = realpath($bundleDir . '/../../app');
        $bundleAppDir = $appRootDir . '/Resources/TestBundle1';

        return [
            'empty dir, nothing to load'                                      => [
                'expectedResult'              => null,
                'expectedRegisteredResources' => [],
                'path'                        => 'unknown dir/'
            ],
            'loading contents'                                                => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.txt'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/test.txt'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/sub1/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.txt'),
                    $this->getPath($bundleDir . '/Resources/folder_to_track/test.txt'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/test.txt'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/sub1/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => -1,
                'fileExtensions'              => []
            ],
            'loading contents filtered by file extensions'                    => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/sub/sub1/test.xml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => -1
            ],
            'loading contents limit nesting level'                            => [
                'expectedResult'              => [
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => 1
            ],
            'loading contents limit nesting level that takes all files exist' => [
                'expectedResult'              => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'expectedRegisteredResources' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    $this->getPath($bundleAppDir . '/folder_to_track/test.xml')
                ],
                'path'                        => 'Resources/folder_to_track/',
                'nestingLevel'                => 2
            ]
        ];
    }

    public function testLoadInHierarchicalModeWithAppRootDirectory()
    {
        $loader = $this->getLoader('Resources/folder_to_track/', ['/\.yml$/', '/\.xml$/'], -1, false);

        $bundleClass = TestBundle1::class;
        $bundleDir = $this->getBundleDir();
        $bundleAppDir = $this->getRootDir() . '/app/Resources/TestBundle1';

        /** @var CumulativeResourceInfo $result */
        $result = $loader->load($bundleClass, $bundleDir, $bundleAppDir);
        $this->assertInstanceOf(CumulativeResourceInfo::class, $result);

        ksort($result->data);
        $this->assertEquals(
            [
                $this->getPath($bundleAppDir . '/folder_to_track/test.xml'),
                'sub' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    'sub1' => [
                        $this->getPath($bundleAppDir . '/folder_to_track/sub/sub1/test.xml')
                    ]
                ]
            ],
            $result->data
        );

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $foundResources = $resource->getFound($bundleClass);
        sort($foundResources);
        $this->assertEquals(
            [
                $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
            ],
            $foundResources
        );
    }

    public function testLoadInHierarchicalMode()
    {
        $loader = $this->getLoader('Resources/folder_to_track/', ['/\.yml$/', '/\.xml$/'], -1, false);

        $bundleClass = TestBundle1::class;
        $bundleDir = $this->getBundleDir();

        /** @var CumulativeResourceInfo $result */
        $result = $loader->load($bundleClass, $bundleDir);
        $this->assertInstanceOf(CumulativeResourceInfo::class, $result);

        ksort($result->data);
        $this->assertEquals(
            [
                $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml'),
                'sub' => [
                    $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                    'sub1' => [
                        $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml')
                    ]
                ]
            ],
            $result->data
        );

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, '', $resource);

        $foundResources = $resource->getFound($bundleClass);
        sort($foundResources);
        $this->assertEquals(
            [
                $this->getPath($bundleDir . '/Resources/folder_to_track/sub/sub1/test.xml'),
                $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml'),
                $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml')
            ],
            $foundResources
        );
    }

    /**
     * @dataProvider isResourceFreshDataProvider
     */
    public function testIsResourceFresh(callable $assertion)
    {
        $loader = $this->getLoader('Resources/folder_to_track/', ['/\.yml$/', '/\.xml$/']);

        $bundleClass = TestBundle1::class;
        $bundleDir = $this->getBundleDir();
        $bundleAppDir = $this->getRootDir() . '/app/Resources/TestBundle1';

        $resource = new CumulativeResource('test_group', new CumulativeResourceLoaderCollection());
        $loader->registerFoundResource($bundleClass, $bundleDir, $bundleAppDir, $resource);
        $loadTime = $this->getLastChangeTime($resource, $bundleClass) + 1;
        // guard
        $this->assertTrue($loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime));

        $assertion($loader, $resource, $loadTime, $bundleClass, $bundleDir, $bundleAppDir);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function isResourceFreshDataProvider(): array
    {
        return [
            'file was added'                                      => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/added.yml');
                    touch($filePath);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was deleted'                                    => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml');
                    unlink($filePath);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was changed'                                    => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.yml');
                    touch($filePath, $loadTime + 1);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was added, but it was overridden by app file'   => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml');
                    touch($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was deleted, but it was overridden by app file' => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml');
                    unlink($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was changed, but it was overridden by app file' => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/test.xml');
                    touch($filePath, $loadTime + 1);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'file was added to new directory'                     => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/added/added.yml');
                    mkdir(dirname($filePath));
                    touch($filePath);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'directory was created'                               => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    mkdir($this->getPath($bundleDir . '/Resources/folder_to_track/added'));
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'directory was deleted'                               => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $fs = new Filesystem();
                    $fs->remove($this->getPath($bundleDir . '/Resources/folder_to_track/sub'));
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked file was added'                            => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/added.txt');
                    touch($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked file was deleted'                          => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/sub/test.txt');
                    unlink($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked file was changed'                          => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleDir . '/Resources/folder_to_track/test.txt');
                    touch($filePath, $loadTime + 1);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'app file was added'                                  => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/added.xml');
                    touch($filePath);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'app file was deleted'                                => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/test.xml');
                    unlink($filePath);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'app file was changed'                                => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/test.xml');
                    touch($filePath, $loadTime + 1);
                    self::assertFalse(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked app file was added'                        => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/added.txt');
                    touch($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked app file was deleted'                      => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/sub/test.txt');
                    unlink($filePath);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ],
            'untracked app file was changed'                      => [
                function (
                    FolderContentCumulativeLoader $loader,
                    CumulativeResource $resource,
                    $loadTime,
                    $bundleClass,
                    $bundleDir,
                    $bundleAppDir
                ) {
                    $filePath = $this->getPath($bundleAppDir . '/folder_to_track/sub/test.txt');
                    touch($filePath, $loadTime + 1);
                    self::assertTrue(
                        $loader->isResourceFresh($bundleClass, $bundleDir, $bundleAppDir, $resource, $loadTime)
                    );
                }
            ]
        ];
    }
}
