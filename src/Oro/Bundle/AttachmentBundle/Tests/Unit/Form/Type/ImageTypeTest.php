<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;
use PHPUnit\Framework\TestCase;

class ImageTypeTest extends TestCase
{
    private ImageType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new ImageType();
    }

    public function testInterface(): void
    {
        $this->assertSame('oro_image', $this->type->getName());
        $this->assertSame(FileType::class, $this->type->getParent());
    }
}
