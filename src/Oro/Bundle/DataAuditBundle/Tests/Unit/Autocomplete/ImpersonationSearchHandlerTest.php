<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\DataAuditBundle\Autocomplete\ImpersonationSearchHandler;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UserBundle\Entity\Impersonation;
use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;

class ImpersonationSearchHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImpersonationSearchHandler */
    private $searchHandler;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    protected function setUp()
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->searchHandler = new ImpersonationSearchHandler($this->doctrineHelper, new StubTranslator());
    }

    public function testGetEntityName()
    {
        $this->assertInternalType('string', $this->searchHandler->getEntityName());
        $this->assertSame(Impersonation::class, $this->searchHandler->getEntityName());
    }

    public function testGetProperties()
    {
        $this->assertInternalType('array', $this->searchHandler->getProperties());
        $this->assertArraySubset(['ipAddress', 'token', 'ipAddressToken'], $this->searchHandler->getProperties());
    }

    public function testConvertItemInvalidType()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected argument of type "Oro\Bundle\UserBundle\Entity\Impersonation", "stdClass" given'
        );

        $this->searchHandler->convertItem(new \stdClass());
    }

    public function testConvertItemNoToken()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected Impersonation contains token'
        );

        $impresonation = $this->createMock(Impersonation::class);

        $this->searchHandler->convertItem($impresonation);
    }

    public function testConvertItemNoIpAddress()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Expected Impersonation contains ipAddress'
        );

        $impresonation = $this->createMock(Impersonation::class);
        $impresonation->expects($this->once())->method('getToken')->willReturn('hash');

        $this->searchHandler->convertItem($impresonation);
    }

    public function testConvertItem()
    {
        $impresonation = $this->createMock(Impersonation::class);
        $impresonation->expects($this->once())->method('getToken')->willReturn('hash');
        $impresonation->expects($this->once())->method('getIpAddress')->willReturn('255.255.255.255');

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
