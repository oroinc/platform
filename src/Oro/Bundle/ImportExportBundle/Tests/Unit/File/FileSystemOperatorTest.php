<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\File;

use Oro\Bundle\ImportExportBundle\File\FileSystemOperator;

class FileSystemOperatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var string */
    protected $existingDir = 'existing';

    /** @var string */
    protected $newDir = 'new';

    /** @var string */
    protected $cacheDir;

    protected function setUp()
    {
        $this->cacheDir = sys_get_temp_dir() . '/FileSystemOperatorTest';
        \mkdir($this->cacheDir);
        \mkdir($this->cacheDir . DIRECTORY_SEPARATOR . $this->existingDir);
        \copy(
            __DIR__ . '/fixtures' . DIRECTORY_SEPARATOR . $this->existingDir . DIRECTORY_SEPARATOR . 'file.csv',
            $this->cacheDir . DIRECTORY_SEPARATOR . $this->existingDir . DIRECTORY_SEPARATOR . 'file.csv'
        );
    }

    protected function tearDown()
    {
        $newDirPath = $this->cacheDir . DIRECTORY_SEPARATOR . $this->newDir;
        if (is_dir($newDirPath)) {
            @\rmdir($newDirPath);
        }
        @\unlink($this->cacheDir . DIRECTORY_SEPARATOR . $this->existingDir . DIRECTORY_SEPARATOR . 'file.csv');
        @\rmdir($this->cacheDir . DIRECTORY_SEPARATOR . $this->existingDir);
        @\rmdir($this->cacheDir);
    }

    /**
     * @dataProvider dirDataProvider
     *
     * @param string $dir
     */
    public function testGetTemporaryDirectory($dir)
    {
        $fs = new FileSystemOperator($this->cacheDir, $dir);
        $expectedDir = $this->cacheDir . DIRECTORY_SEPARATOR . $dir;
        self::assertEquals($expectedDir, $fs->getTemporaryDirectory());
        self::assertFileExists($expectedDir);
    }

    public function dirDataProvider()
    {
        return [
            [$this->existingDir],
            [$this->newDir],
        ];
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Can't read file unknown.csv
     */
    public function testGetTemporaryFileException()
    {
        $fs = new FileSystemOperator($this->cacheDir, $this->existingDir);
        $fs->getTemporaryFile('unknown.csv');
    }

    public function testGetTemporaryFile()
    {
        $fs = new FileSystemOperator($this->cacheDir, $this->existingDir);
        self::assertInstanceOf('\SplFileObject', $fs->getTemporaryFile('file.csv'));
    }

    public function testGenerateTemporaryFileName()
    {
        $fs = new FileSystemOperator($this->cacheDir, $this->existingDir);
        $fileName = $fs->generateTemporaryFileName('test', 'ext');
        self::assertStringEndsWith('ext', $fileName);
        self::assertContains(DIRECTORY_SEPARATOR . 'test', $fileName);
        self::assertContains(date('Y_m_d_H_i_'), $fileName);
    }
}
