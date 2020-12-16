<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\EventListener\EmailAddressAssociationMetadataListener;

class EmailAddressAssociationMetadataListenerTest extends \PHPUnit\Framework\TestCase
{
    private const EMAIL_ADDRESS_PROXY_CLASS = 'Test\EmailAddressProxy';

    /** @var EmailAddressManager|\PHPUnit\Framework\MockObject\MockObject */
    private $emailAddressManager;

    /** @var EmailAddressAssociationMetadataListener */
    private $listener;

    protected function setUp(): void
    {
        $this->emailAddressManager = $this->createMock(EmailAddressManager::class);
        $this->emailAddressManager->expects(self::any())
            ->method('getEmailAddressProxyClass')
            ->willReturn(self::EMAIL_ADDRESS_PROXY_CLASS);

        $this->listener = new EmailAddressAssociationMetadataListener($this->emailAddressManager);
    }

    public function testLoadClassMetadata()
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
