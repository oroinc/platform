<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailLinkToScopeType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

class EmailLinkToScopeTypeTest extends TypeTestCase
{
    public function testGetParent()
    {
        $type = new EmailLinkToScopeType();
        $this->assertEquals('choice', $type->getParent());
    }

    public function testGetName()
    {
        $type = new EmailLinkToScopeType();
        $this->assertEquals(EmailLinkToScopeType::NAME, $type->getName());
    }
}
