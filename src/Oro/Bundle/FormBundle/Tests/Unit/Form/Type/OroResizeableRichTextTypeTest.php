<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Symfony\Component\Form\Test\TypeTestCase;

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
