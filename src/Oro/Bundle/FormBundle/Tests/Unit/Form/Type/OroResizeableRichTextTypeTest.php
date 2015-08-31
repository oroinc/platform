<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;

class OroResizeableRichTextTypeTest extends TypeTestCase
{
    public function testGetName()
    {
        $type = new OroResizeableRichTextType([]);
        $this->assertEquals('oro_resizeable_rich_text', $type->getName());
    }

    public function testGetParent()
    {
        $type = new OroResizeableRichTextType([]);
        $this->assertEquals('oro_rich_text', $type->getParent());
    }
}
