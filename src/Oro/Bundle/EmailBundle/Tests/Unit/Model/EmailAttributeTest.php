<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\EmailAttribute;
use PHPUnit\Framework\TestCase;

class EmailAttributeTest extends TestCase
{
    public function testEmailAttributeShouldBeConstructed(): void
    {
        $emailAttribute = new EmailAttribute('attr', true);
        $this->assertEquals('attr', $emailAttribute->getName());
        $this->assertTrue($emailAttribute->isAssociation());
    }
}
