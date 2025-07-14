<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\DataAuditBundle\Autocomplete\ImpersonationSearchHandler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class ImpersonationSearchHandlerTest extends TestCase
{
    private ImpersonationSearchHandler $searchHandler;
    private DoctrineHelper&MockObject $doctrineHelper;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function (string $key) {
                return sprintf('[trans]%s[/trans]', $key);
            });

        $this->searchHandler = new ImpersonationSearchHandler($this->doctrineHelper, $translator);
    }

    public function testGetEntityName(): void
    {
        $this->assertIsString($this->searchHandler->getEntityName());
        $this->assertSame(Impersonation::class, $this->searchHandler->getEntityName());
    }

    public function testGetProperties(): void
    {
        $this->assertIsArray($this->searchHandler->getProperties());

        $properties = $this->searchHandler->getProperties();

        $this->assertSame('ipAddress', $properties[0]);
        $this->assertSame('token', $properties[1]);
        $this->assertSame('ipAddressToken', $properties[2]);
    }

    public function testConvertItemInvalidType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\UserBundle\Entity\Impersonation", "stdClass" given'
        );

        $this->searchHandler->convertItem(new \stdClass());
    }

    public function testConvertItemNoToken(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected Impersonation contains token'
        );

        $impresonation = $this->createMock(Impersonation::class);

        $this->searchHandler->convertItem($impresonation);
    }

    public function testConvertItemNoIpAddress(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected Impersonation contains ipAddress'
        );

        $impresonation = $this->createMock(Impersonation::class);
        $impresonation->expects($this->once())
            ->method('getToken')
            ->willReturn('hash');

        $this->searchHandler->convertItem($impresonation);
    }

    public function testConvertItem(): void
    {
        $impresonation = $this->createMock(Impersonation::class);
        $impresonation->expects($this->once())
            ->method('getToken')
            ->willReturn('hash');
        $impresonation->expects($this->once())
            ->method('getIpAddress')
            ->willReturn('255.255.255.255');

        $this->assertSame(
            [
                'id' => null,
                'ipAddress' => '255.255.255.255',
                'token' => 'hash',
                'ipAddressToken' => '[trans]oro.dataaudit.datagrid.author_impersonation_filter[/trans]',
            ],
            $this->searchHandler->convertItem($impresonation)
        );
    }
}
