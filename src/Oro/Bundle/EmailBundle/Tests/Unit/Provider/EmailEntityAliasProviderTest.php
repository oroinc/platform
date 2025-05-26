<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Provider\EmailEntityAliasProvider;
use PHPUnit\Framework\TestCase;

class EmailEntityAliasProviderTest extends TestCase
{
    private const string EMAIL_ADDRESS_PROXY_CLASS = 'Test\EmailAddressProxy';

    private EmailEntityAliasProvider $entityAliasProvider;

    #[\Override]
    protected function setUp(): void
    {
        $emailAddressManager = $this->createMock(EmailAddressManager::class);
        $emailAddressManager->expects($this->once())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->entityAliasProvider = new EmailEntityAliasProvider($emailAddressManager);
    }

    public function testGetEntityAlias(): void
    {
        $this->assertNull($this->entityAliasProvider->getEntityAlias(\stdClass::class));

        $entityAlias = $this->entityAliasProvider->getEntityAlias(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->assertNotNull($entityAlias);
        $this->assertEquals('emailaddress', $entityAlias->getAlias());
        $this->assertEquals('emailaddresses', $entityAlias->getPluralAlias());
    }
}
