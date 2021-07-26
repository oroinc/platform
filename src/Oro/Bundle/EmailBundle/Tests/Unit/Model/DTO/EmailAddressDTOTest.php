<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model\DTO;

use Oro\Bundle\EmailBundle\Model\DTO\EmailAddressDTO;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;

class EmailAddressDTOTest extends \PHPUnit\Framework\TestCase
{
    public function testGetEmail(): void
    {
        $email = 'test@example.com';

        $obj = new EmailAddressDTO($email);

        $this->assertInstanceOf(EmailHolderInterface::class, $obj);
        $this->assertSame($email, $obj->getEmail());
    }
}
