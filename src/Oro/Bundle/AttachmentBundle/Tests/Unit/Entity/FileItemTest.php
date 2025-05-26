<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use PHPUnit\Framework\TestCase;

class FileItemTest extends TestCase
{
    use EntityTestCaseTrait;

    private FileItem $entity;

    #[\Override]
    protected function setUp(): void
    {
        $this->entity = new FileItem();
    }

    public function testAccessors(): void
    {
        $properties = [
            ['id', 1],
            ['file', new File()],
            ['sortOrder', 10, true],

        ];

        self::assertPropertyAccessors($this->entity, $properties);
    }

    public function testIsEmpty(): void
    {
        $this->assertInstanceof(EmptyItem::class, $this->entity);
        $this->assertTrue($this->entity->isEmpty());

        $this->entity->setFile(new File());

        $this->assertFalse($this->entity->isEmpty());
    }
}
