<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Model;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ImportDataTest extends TestCase
{
    public function testEmpty(): void
    {
        $data = new ImportData();

        self::assertNull($data->getFile());
        self::assertNull($data->getProcessorAlias());
    }

    public function testFileSetterAndGetter(): void
    {
        $data = new ImportData();

        $file = $this->createMock(UploadedFile::class);
        $data->setFile($file);
        self::assertSame($file, $data->getFile());

        $data->setFile(null);
        self::assertNull($data->getFile());
    }

    public function testProcessorAliasSetterAndGetter(): void
    {
        $data = new ImportData();

        $processorAlias = 'test';
        $data->setProcessorAlias($processorAlias);
        self::assertSame($processorAlias, $data->getProcessorAlias());

        $data->setProcessorAlias(null);
        self::assertNull($data->getProcessorAlias());
    }
}
