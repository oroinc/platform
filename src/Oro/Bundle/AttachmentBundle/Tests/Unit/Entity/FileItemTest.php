<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Entity;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Oro\Bundle\AttachmentBundle\Model\ExtendFileItem;
use Oro\Bundle\FormBundle\Entity\EmptyItem;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class FileItemTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /** @var FileItem */
    private $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->entity = new FileItem();
    }

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['file', new File()],
            ['sortOrder', 10, true],

        ];

        static::assertPropertyAccessors($this->entity, $properties);
    }

    public function testIsEmpty()
    {
        $this->assertInstanceof(EmptyItem::class, $this->entity);
        $this->assertTrue($this->entity->isEmpty());

        $this->entity->setFile(new File());

        $this->assertFalse($this->entity->isEmpty());
    }

    public function testExtendModel()
    {
        $this->assertInstanceof(ExtendFileItem::class, $this->entity);
    }
}
