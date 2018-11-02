<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Provider;

use Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\Translation\Reader\TranslationReader;

class TranslationServiceProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $adapter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $dumper;

    /** @var TranslationServiceProvider */
    protected $service;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $databasePersister;

    /** @var string */
    protected $className = 'Oro\Bundle\TranslationBundle\Provider\TranslationServiceProvider';

    /** @var string */
    protected $testPath;

    protected function setUp()
    {
        $this->adapter = $this->createMock('Oro\Bundle\TranslationBundle\Provider\CrowdinAdapter');
        $this->dumper  = $this->createMock('Oro\Bundle\TranslationBundle\Provider\JsTranslationDumper');

        $this->databasePersister = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DatabasePersister')
            ->disableOriginalConstructor()
            ->getMock();

        $this->testPath = $this->getTempDir('trans');
        $this->service = new TranslationServiceProvider(
            $this->adapter,
            $this->dumper,
            new TranslationReader(),
            $this->databasePersister,
            $this->testPath
        );
    }

    /**
     * Test upload method
     */
    public function testUpload()
    {
        $mode = 'add';

        $this->adapter->expects($this->once())
            ->method('upload')
            ->with($this->isType('array'), $mode);

        $this->service->setAdapter($this->adapter);
        $this->assertEquals($this->adapter, $this->service->getAdapter());

        $this->service->upload($this->getLangFixturesDir(), $mode);
    }

    /**
     * @dataProvider updateDataProvider
     */
    public function testUpdate($isDownloaded, $downloadFileExists)
    {
        $service = $this->getServiceMock(
            ['download', 'upload', 'cleanup'],
            [$this->adapter, $this->dumper, new TranslationReader(), $this->databasePersister, $this->testPath]
        );

        $dir = $this->getLangFixturesDir();

        if ($isDownloaded) {
            $mock = $service->expects($this->once())
                ->method('download');

            if ($downloadFileExists) {
                $mock->will(
                    $this->returnCallback(
                        function ($pathToSave) {
                            $tmpDir = dirname($pathToSave);
                            $path   = $tmpDir . DIRECTORY_SEPARATOR . 'en';
                            if (!is_dir($path)) {
                                mkdir($path, 0777, true);
                            }
                            touch($path . DIRECTORY_SEPARATOR . 'messages.en.yml');
                            return true;
                        }
                    )
                );
            } else {
                $mock->will($this->returnValue(true));
            }
        } else {
            $service->expects($this->once())
                ->method('download')
                ->will($this->returnValue(false));

            $this->assertFalse($service->update($dir));
            return;
        }

        $service->expects($this->once())
            ->method('upload')
            ->with($this->isType('string'), 'update');

        $service->expects($this->once())
            ->method('cleanup');

        $service->update([$dir]);
    }

    public function testDownloadAndLoad()
    {
        $service = $this->getServiceMock(
            ['cleanup', 'renameFiles', 'apply', 'unzip'],
            [$this->adapter, $this->dumper, new TranslationReader(), $this->databasePersister, $this->testPath]
        );

        $tempDir = $this->testPath . DIRECTORY_SEPARATOR . ltrim(uniqid(), DIRECTORY_SEPARATOR);
        $path = $tempDir . DIRECTORY_SEPARATOR . 'zip';
        mkdir(dirname($path), 0777, true);
        touch($path . TranslationServiceProvider::FILE_NAME_SUFFIX);

        $service->expects($this->exactly(2))
            ->method('cleanup');

        $this->adapter->expects($this->once())
            ->method('download')
            ->will($this->returnValue(true));

        $service->expects($this->once())
            ->method('unzip')
            ->will($this->returnValue(true));

        $service->expects($this->once())
            ->method('renameFiles');

        $service->expects($this->once())
            ->method('apply')
            ->will($this->returnValue(['en']));

        $this->dumper->expects($this->once())
            ->method('dumpTranslations')
            ->with(['en']);

        $service->download($path, ['Oro'], 'en');
        $service->loadTranslatesFromFile($path, 'en');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDownloadException()
    {
        $service = $this->getServiceMock(
            ['cleanup', 'renameFiles', 'apply', 'unzip'],
            [$this->adapter, $this->dumper, new TranslationReader(), $this->databasePersister, $this->testPath]
        );

        $path = $this->testPath . DIRECTORY_SEPARATOR;
        $path = $path . ltrim(uniqid(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'zip';
        mkdir(dirname($path), 0777, true);
        touch($path . TranslationServiceProvider::FILE_NAME_SUFFIX);

        $service->expects($this->once())
            ->method('cleanup');

        $this->adapter->expects($this->once())
            ->method('download')
            ->will($this->returnValue(true));

        $ex = new \RuntimeException('error', \ZipArchive::ER_NOZIP);
        $service->expects($this->once())
            ->method('unzip')
            ->will($this->throwException($ex));

        $service->download($path, ['Oro'], 'en');
        $service->loadTranslatesFromFile($path, 'en');
    }

    public function testCleanUp()
    {
        $path     = $this->testPath . DIRECTORY_SEPARATOR;
        $dir      = $path . ltrim(uniqid(), DIRECTORY_SEPARATOR);
        $path     = $dir . DIRECTORY_SEPARATOR . 'zip';
        $fileName = $path . TranslationServiceProvider::FILE_NAME_SUFFIX;
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        touch($fileName);

        $this->assertFileExists($fileName);

        $method = new \ReflectionMethod($this->className, 'cleanup');
        $method->setAccessible(true);
        $method->invoke($this->service, $dir);
        $method->invoke($this->service, $dir . '1');

        $this->assertFileNotExists($fileName);
    }

    public function testApply()
    {
        $apply = new \ReflectionMethod($this->className, 'apply');
        $apply->setAccessible(true);

        $basePath = $target = $this->testPath . DIRECTORY_SEPARATOR
            . ltrim(uniqid(), DIRECTORY_SEPARATOR);
        $target   = $basePath . DIRECTORY_SEPARATOR . 'target';
        $source   = $basePath . DIRECTORY_SEPARATOR . 'source';

        $tmpDirName = $source . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'test';
        mkdir($tmpDirName, 0777, true);
        touch($tmpDirName . DIRECTORY_SEPARATOR . 'test.en.yml');

        $apply->invoke($this->service, $target, $source);

        $cleanup = new \ReflectionMethod($this->className, 'cleanup');
        $cleanup->setAccessible(true);
        $cleanup->invoke($this->service, $basePath);
    }

    /**
     * Data provider for testUpdate
     */
    public function updateDataProvider()
    {
        return [
            'downloaded ok'       => [true, true],
            'downloaded ok empty' => [true, false],
            'not downloaded'      => [false, false]
        ];
    }

    /**
     * @dataProvider processDirsProvider
     *
     * @param $inputDirs
     * @param $expectedDirs
     */
    public function testProcessDirs($inputDirs, $expectedDirs)
    {
        $method = new \ReflectionMethod(
            $this->className,
            'processDirs'
        );
        $method->setAccessible(true);

        $this->assertEquals($expectedDirs, $method->invoke($this->service, $inputDirs));
    }

    /**
     * @return array
     */
    public function processDirsProvider()
    {
        $dir1 = implode(DIRECTORY_SEPARATOR, ['some', 'dir1']);
        $dir2 = implode(DIRECTORY_SEPARATOR, ['some', 'dir2']);

        return [
            'string' => [
                'inputDirs' => $dir1,
                'expectedDirs' => [$dir1],
            ],
            'string not trimmed' => [
                'inputDirs' => $dir1 . DIRECTORY_SEPARATOR,
                'expectedDirs' => [$dir1],
            ],
            'array' => [
                'inputDirs' => [$dir1, $dir2 . DIRECTORY_SEPARATOR],
                'expectedDirs' => [$dir1, $dir2],
            ],
        ];
    }

    public function testRenameFiles()
    {
        $targetPath = $this->testPath . DIRECTORY_SEPARATOR;
        $targetPath = $targetPath . ltrim(uniqid('download_'), DIRECTORY_SEPARATOR);
        mkdir($targetPath, 0777, true);

        $files = [
            $targetPath . DIRECTORY_SEPARATOR . 'messages.en.yml',
            $targetPath . DIRECTORY_SEPARATOR . 'validation.en.yml'
        ];

        $filesExpected = [
            $targetPath . DIRECTORY_SEPARATOR . 'messages.en_US.yml',
            $targetPath . DIRECTORY_SEPARATOR . 'validation.en_US.yml'
        ];

        foreach ($files as $file) {
            touch($file);
        }

        $service = $this->getServiceMock(
            ['__construct'],
            [$this->adapter, $this->dumper, new TranslationReader(), $this->databasePersister, $this->testPath]
        );

        $method = new \ReflectionMethod(
            $this->className,
            'renameFiles'
        );
        $method->setAccessible(true);

        $method->invoke($service, '.en.', '.en_US.', $targetPath);

        foreach ($files as $k => $file) {
            $this->assertFileNotExists($file);
            $this->assertFileExists($filesExpected[$k]);
        }
    }

    /**
     * @return string
     */
    protected function getLangFixturesDir()
    {
        return __DIR__ . '/../Fixtures/Resources/lang-pack/';
    }

    /**
     * @param array $methods
     * @param array $args
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|TranslationServiceProvider
     */
    protected function getServiceMock($methods = [], $args = [])
    {
        return $this->getMockBuilder($this->className)->setMethods($methods)->setConstructorArgs($args)->getMock();
    }
}
