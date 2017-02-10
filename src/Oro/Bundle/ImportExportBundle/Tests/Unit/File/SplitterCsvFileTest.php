<?php

namespace Oro\Bundle\ImportExportBundle\Tests\File;

use Oro\Bundle\ImportExportBundle\File\SplitterCsvFile;
use Oro\Bundle\ImportExportBundle\Reader\CsvFileReader;

class SplitterCsvFileTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CsvFileReader
     */
    private function createCsvFileReaderMock()
    {
        return $this->createMock(CsvFileReader::class, [], [], '', false);
    }

    private $cacheDir;

    private $filePath;

    protected function setUp()
    {
        $fixturesDir = __DIR__.DIRECTORY_SEPARATOR.'fixtures'.DIRECTORY_SEPARATOR;
        $this->cacheDir = $fixturesDir.'cache'.DIRECTORY_SEPARATOR;
        $this->filePath = 'import_file.csv';
        @mkdir($this->cacheDir);
    }

    protected function tearDown()
    {
        $this->cacheDir;
        if (is_dir($this->cacheDir)) {
            $files = glob($this->cacheDir . '*', GLOB_MARK);
            foreach ($files as $file) {
                @unlink($file);
            }

            @rmdir($this->cacheDir);
        }
    }

    public function testShouldReturnNotSplitFile()
    {
        $storage = $this->cacheDir;
        $importFile = $this->filePath;
        $csvReader = $this->createCsvFileReaderMock();
        $parsedData = $this->onConsecutiveCalls(
            ['test1', 'test1'],
            ['test2', 'test2'],
            ['test3', 'test3'],
            ['test4', 'test4'],
            ['test5', 'test5']
        );
        $csvReader
            ->expects($this->exactly(6))
            ->method('read')
            ->will($parsedData);

        $self = new SplitterCsvFile($csvReader, $storage, 100);
        $files = $self->getSplittedFilesNames($importFile);
        $this->assertCount(1, $files);
        $this->assertContains(current($files), $importFile);
    }

    public function testShouldReturnTwoSplitFiles()
    {
        $storage = $this->cacheDir;
        $importFile = $this->filePath;
        $csvReader = $this->createCsvFileReaderMock();
        $csvReader
            ->expects($this->exactly(151))
            ->method('read')
            ->will($this->returnCallback(
                function () use (&$i) {
                    $i++;
                    if ($i > 150) {
                        return null;
                    }
                    return [1, 2];
                }
            ));
        $self = new SplitterCsvFile($csvReader, $storage, 100);
        $files = $self->getSplittedFilesNames($importFile);
        $this->assertCount(2, $files);
        $file1 = dirname(current($files));
        $file2 = dirname(next($files));
        $this->assertContains($file1, $this->cacheDir.'chunk_1_'.$importFile);
        $this->assertContains($file2, $this->cacheDir.'chunk_2_'.$importFile);
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);
    }
}
