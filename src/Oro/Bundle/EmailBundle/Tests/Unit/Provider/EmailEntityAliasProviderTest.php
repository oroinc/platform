<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Provider\EmailEntityAliasProvider;

class EmailEntityAliasProviderTest extends \PHPUnit\Framework\TestCase
{
    private const EMAIL_ADDRESS_PROXY_CLASS = 'Test\EmailAddressProxy';

    /** @var EmailEntityAliasProvider */
    private $entityAliasProvider;

    protected function setUp(): void
    {
        $emailAddressManager = $this->createMock(EmailAddressManager::class);
        $emailAddressManager->expects($this->once())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->entityAliasProvider = new EmailEntityAliasProvider($emailAddressManager);
    }

    public function testGetEntityAlias()
    {
        $this->assertNull($this->entityAliasProvider->getEntityAlias(\stdClass::class));

        $entityAlias = $this->entityAliasProvider->getEntityAlias(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->assertNotNull($entityAlias);
        $this->assertEquals('emailaddress', $entityAlias->getAlias());
        $this->assertEquals('emailaddresses', $entityAlias->getPluralAlias());
    }
}
