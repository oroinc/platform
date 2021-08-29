<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Entity\Provider;

use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailOwnerProviderStorage;

class EmailOwnerProviderStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testStorage()
    {
        $provider1 = $this->createMock(EmailOwnerProviderInterface::class);
        $provider2 = $this->createMock(EmailOwnerProviderInterface::class);

        $storage = new EmailOwnerProviderStorage();
        $storage->addProvider($provider1);
        $storage->addProvider($provider2);

        $result = $storage->getProviders();

        $this->assertCount(2, $result);
        $this->assertSame($provider1, $result[0]);
        $this->assertSame($provider2, $result[1]);
    }
}
