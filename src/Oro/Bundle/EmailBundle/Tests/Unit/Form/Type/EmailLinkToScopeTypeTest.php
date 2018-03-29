<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EmailBundle\Form\Type\EmailLinkToScopeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;

class EmailLinkToScopeTypeTest extends TypeTestCase
{
    public function testGetParent()
    {
        $type = new EmailLinkToScopeType();
        $this->assertEquals(ChoiceType::class, $type->getParent());
    }

    public function testGetName()
    {
        $type = new EmailLinkToScopeType();
        $this->assertEquals(EmailLinkToScopeType::NAME, $type->getName());
    }
}
