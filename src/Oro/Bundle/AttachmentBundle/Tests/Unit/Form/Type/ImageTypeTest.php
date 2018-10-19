<?php

namespace Oro\Bundle\AttachmentBundle\Tests\Unit\Form\Type;

use Oro\Bundle\AttachmentBundle\Form\Type\FileType;
use Oro\Bundle\AttachmentBundle\Form\Type\ImageType;

class ImageTypeTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImageType */
    protected $type;

    public function setUp()
    {
        $this->type = new ImageType();
    }

    public function testInterface()
    {
        $this->assertSame('oro_image', $this->type->getName());
        $this->assertSame(FileType::class, $this->type->getParent());
    }
}
