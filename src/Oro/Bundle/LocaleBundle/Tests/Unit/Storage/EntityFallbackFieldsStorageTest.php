<?php
namespace Oro\Bundle\LocaleBundle\Tests\Unit\Storage;

use Oro\Bundle\LocaleBundle\Storage\EntityFallbackFieldsStorage;
use PHPUnit\Framework\TestCase;

class EntityFallbackFieldsStorageTest extends TestCase
{
    public function testGetFieldMap(): void
    {
        $storage = new EntityFallbackFieldsStorage([
            'Test\Entity' => ['testField']
        ]);

        $expected = [
            'Test\Entity' => ['testField']
        ];
        $actual = $storage->getFieldMap();

        $this->assertEquals($expected, $actual);
    }
}
