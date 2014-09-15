<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Manager;

use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithIcon;
use Oro\Bundle\IntegrationBundle\Tests\Unit\Stub\IntegrationTypeWithoutIcon;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

class TypesRegistryTest extends \PHPUnit_Framework_TestCase
{
    const CHANNEL_TYPE_ONE   = 'type1';
    const CHANNEL_TYPE_TWO   = 'type2';
    const TRANSPORT_TYPE_ONE = 'transport1';
    const TRANSPORT_TYPE_TWO = 'transport2';

    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var TransportInterface */
    protected $transport1;

    /** @var TransportInterface */
    protected $transport2;

    public function setUp()
    {
        $this->transport1 = $this->getMockBuilder('Oro\Bundle\IntegrationBundle\Provider\TransportInterface')
            ->disableOriginalConstructor()->getMock();
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
            'Doctrine\Common\Collections\Collection',
            $this->typesRegistry->getRegisteredChannelTypes()
        );

        $this->assertContainsOnlyInstancesOf(
            'Oro\Bundle\IntegrationBundle\Provider\ChannelInterface',
            $this->typesRegistry->getRegisteredChannelTypes()
        );
    }

    public function testGetAvailableChannelTypesChoiceList()
    {
        $this->assertEquals(
            [self::CHANNEL_TYPE_ONE => "oro.type1.label", self::CHANNEL_TYPE_TWO => "oro.type2.label"],
            $this->typesRegistry->getAvailableChannelTypesChoiceList()
        );
    }

    public function testGetAvailableIntegrationTypesChoiceListWithIcon()
    {
        $this->assertEquals(
            [
                self::CHANNEL_TYPE_ONE => ["label" => "oro.type1.label", "icon" => "bundles/acmedemo/img/logo.png"],
                self::CHANNEL_TYPE_TWO => ["label" => "oro.type2.label"],
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

    /**
     * @expectedException \LogicException
     */
    public function testGetTransportType1()
    {
        $this->assertEquals(
            $this->transport1,
            $this->typesRegistry->getTransportType('error1', 'error2')
        );
    }

    public function testGetRegisteredTransportTypes()
    {
        $this->assertInstanceOf(
            'Doctrine\Common\Collections\Collection',
            $this->typesRegistry->getRegisteredTransportTypes(self::CHANNEL_TYPE_ONE)
        );
    }
}
