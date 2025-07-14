<?php

namespace Oro\Component\Layout\Tests\Unit\Loader\Generator;

use Oro\Component\Layout\Loader\Generator\GeneratorData;
use PHPUnit\Framework\TestCase;

class GeneratorDataTest extends TestCase
{
    public function testFilenameShouldBeOptional(): void
    {
        $data = new GeneratorData(uniqid('testSource', true));
        $this->assertNull($data->getFilename());
    }

    public function testSourceDataGetter(): void
    {
        $source = uniqid('testSource', true);
        $data = new GeneratorData($source);

        $this->assertSame($source, $data->getSource());

        $newSource = uniqid('newTestSource', true);
        $data->setSource($newSource);

        $this->assertSame($newSource, $data->getSource());
    }

    public function testFilenameSetter(): void
    {
        $data = new GeneratorData(uniqid('testSource', true));
        $this->assertNull($data->getFilename());
        $filename = uniqid('testFilename', true);
        $data = new GeneratorData(uniqid('testSource', true), $filename);
        $this->assertSame($filename, $data->getFilename());
    }
}
