<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Transport;

class AbstractRestTransportTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $clientFactory;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $transport;

    protected function setUp()
    {
        $this->clientFactory = $this->getMock(
            'Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientFactoryInterface'
        );

        $this->transport = $this->getMockBuilder(
            'Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Transport\\AbstractRestTransport'
        )->getMockForAbstractClass();
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testInitWorks()
    {
        $expectedBaseUrl = 'https://example.com/api/v2';
        $expectedClientOptions = ['auth' => ['username', 'password']];

        $expectedClient = $this->getMock(
            'Oro\\Bundle\\IntegrationBundle\\Provider\\Rest\\Client\\RestClientInterface'
        );

        $entity = $this->getMockBuilder('Oro\\Bundle\\IntegrationBundle\\Entity\\Transport')
            ->disableOriginalConstructor()
            ->getMock();

        $settings = $this->getMock('Symfony\\Component\\HttpFoundation\\ParameterBag');

        $entity->expects($this->atLeastOnce())
            ->method('getSettingsBag')
            ->will($this->returnValue($settings));

        $this->transport->expects($this->once())
            ->method('getClientBaseUrl')
            ->with($settings)
            ->will($this->returnValue($expectedBaseUrl));

        $this->transport->expects($this->once())
            ->method('getClientOptions')
            ->with($settings)
            ->will($this->returnValue($expectedClientOptions));

        $this->clientFactory->expects($this->once())
            ->method('createRestClient')
            ->with($expectedBaseUrl, $expectedClientOptions)
            ->will($this->returnValue($expectedClient));

        $this->transport->init($entity);

        $this->assertAttributeSame($expectedClient, 'client', $this->transport);
        $this->assertSame($expectedClient, $this->transport->getClient());
    }

    /**
     * @expectedException \Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage REST Transport isn't configured properly.
     */
    public function testGetClientFails()
    {
        $this->transport->getClient();
    }
}
