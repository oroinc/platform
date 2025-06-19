<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\EventListener\EmailAddressAssociationMetadataListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EmailAddressAssociationMetadataListenerTest extends TestCase
{
    private const string EMAIL_ADDRESS_PROXY_CLASS = 'Test\EmailAddressProxy';

    private EmailAddressManager&MockObject $emailAddressManager;
    private EmailAddressAssociationMetadataListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->emailAddressManager->expects(self::any())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->listener = new EmailAddressAssociationMetadataListener($this->emailAddressManager);
    }

    public function testLoadClassMetadata(): void
    {
        $metadata = new ClassMetadata('Test\Entity');
        $metadata->associationMappings = [
            'association1' => [],
            'association2' => ['targetEntity' => 'Test\TargetEntity'],
            'association3' => ['targetEntity' => EmailAddress::class]
        ];

        $event = new LoadClassMetadataEventArgs(
            $metadata,
            $this->createMock(EntityManagerInterface::class)
        );
        $this->listener->loadClassMetadata($event);

        self::assertFalse(isset($metadata->associationMappings['association1']['targetEntity']));
        self::assertEquals(
            'Test\TargetEntity',
            $metadata->associationMappings['association2']['targetEntity']
        );
        self::assertEquals(
            self::EMAIL_ADDRESS_PROXY_CLASS,
            $metadata->associationMappings['association3']['targetEntity']
        );
    }
}
