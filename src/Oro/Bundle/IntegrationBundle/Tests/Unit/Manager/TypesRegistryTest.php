<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Provider\ChannelInterface;
use Oro\Bundle\IntegrationBundle\Provider\ConnectorInterface;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithIcon;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithoutIcon;

class TypesRegistryTest extends \PHPUnit\Framework\TestCase
{
    private const CHANNEL_TYPE_ONE = 'type1';
    private const CHANNEL_TYPE_TWO = 'type2';
    private const TRANSPORT_TYPE_ONE = 'transport1';
    private const TRANSPORT_TYPE_TWO = 'transport2';

    /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport1;

    /** @var TransportInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $transport2;

    /** @var TypesRegistry */
    private $typesRegistry;

    protected function setUp(): void
    {
        $this->transport1 = $this->createMock(TransportInterface::class);
        $this->transport2 = clone $this->transport1;

        $this->typesRegistry = new TypesRegistry();
        $this->typesRegistry->addChannelType(self::CHANNEL_TYPE_ONE, new IntegrationTypeWithIcon());
        $this->typesRegistry->addTransportType(self::TRANSPORT_TYPE_ONE, self::CHANNEL_TYPE_ONE, $this->transport1);

        $this->typesRegistry->addChannelType(self::CHANNEL_TYPE_TWO, new IntegrationTypeWithoutIcon());
        $this->typesRegistry->addTransportType(self::TRANSPORT_TYPE_TWO, self::CHANNEL_TYPE_TWO, $this->transport2);
    }

    public function testGetRegisteredChannelTypes()
    {
        $this->assertInstanceOf(
            Collection::class,
            $this->typesRegistry->getRegisteredChannelTypes()
        );

        $this->assertContainsOnlyInstancesOf(
            ChannelInterface::class,
            $this->typesRegistry->getRegisteredChannelTypes()
        );
    }

    public function testGetAvailableChannelTypesChoiceList()
    {
        $this->assertEquals(
            ['oro.type1.label' => self::CHANNEL_TYPE_ONE, 'oro.type2.label' => self::CHANNEL_TYPE_TWO],
            $this->typesRegistry->getAvailableChannelTypesChoiceList()
        );
    }

    public function testGetAvailableIntegrationTypesChoiceListWithIcon()
    {
        $this->assertEquals(
            [
                self::CHANNEL_TYPE_ONE => ['label' => 'oro.type1.label', 'icon' => 'bundles/acmedemo/img/logo.png'],
                self::CHANNEL_TYPE_TWO => ['label' => 'oro.type2.label'],
            ],
            $this->typesRegistry->getAvailableIntegrationTypesDetailedData()
        );
    }

    public function testGetTransportType()
    {
        $this->assertEquals(
            $this->transport1,
            $this->typesRegistry->getTransportType(self::CHANNEL_TYPE_ONE, self::TRANSPORT_TYPE_ONE)
        );
    }

    public function testGetTransportType1()
    {
        $this->expectException(\LogicException::class);
        $this->assertEquals(
            $this->transport1,
            $this->typesRegistry->getTransportType('error1', 'error2')
        );
    }

    public function testGetRegisteredTransportTypes()
    {
        $this->assertInstanceOf(
            Collection::class,
            $this->typesRegistry->getRegisteredTransportTypes(self::CHANNEL_TYPE_ONE)
        );
    }

    public function testSupportsSyncWithConnectors()
    {
        $expectedIntegrationType = 'someType';

        $this->typesRegistry->addConnectorType(
            $expectedIntegrationType.'Type',
            $expectedIntegrationType,
            $this->createMock(ConnectorInterface::class)
        );

        $this->assertTrue($this->typesRegistry->supportsSync($expectedIntegrationType));
    }

    public function testSupportsSyncWithoutConnectors()
    {
        $this->assertFalse($this->typesRegistry->supportsSync('someIntegrationType'));
    }
}
