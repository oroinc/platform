<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Provider\EmailEntityAliasProvider;

class EmailEntityAliasProviderTest extends \PHPUnit_Framework_TestCase
{
    const EMAIL_ADDRESS_PROXY_CLASS = 'Test\EmailAddressProxy';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $emailAddressManager;

    /** @var EmailEntityAliasProvider */
    protected $entityAliasProvider;

    protected function setUp()
    {
        $this->emailAddressManager = $this->getMockBuilder('Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->emailAddressManager->expects($this->once())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->entityAliasProvider = new EmailEntityAliasProvider($this->emailAddressManager);
    }

    public function testGetEntityAlias()
    {
        $this->assertNull($this->entityAliasProvider->getEntityAlias('SomeClass'));

        $entityAlias = $this->entityAliasProvider->getEntityAlias(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->assertNotNull($entityAlias);
        $this->assertEquals('emailaddress', $entityAlias->getAlias());
        $this->assertEquals('emailaddresses', $entityAlias->getPluralAlias());
    }
}
