<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroResizeableRichTextType;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Symfony\Component\Form\Test\TypeTestCase;

class OroResizeableRichTextTypeTest extends TypeTestCase
{
    public function testGetParent()
    {
        $type = new OroResizeableRichTextType([]);
        $this->assertEquals(OroRichTextType::class, $type->getParent());
    }
}
