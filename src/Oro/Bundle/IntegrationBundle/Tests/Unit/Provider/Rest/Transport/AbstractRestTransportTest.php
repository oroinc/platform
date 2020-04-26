<?php

namespace Oro\Bundle\IntegrationBundle\Tests\Unit\Provider\Rest\Transport;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Oro\Bundle\IntegrationBundle\Exception\InvalidConfigurationException;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Transport\AbstractRestTransport;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\ParameterBag;

class AbstractRestTransportTest extends \PHPUnit\Framework\TestCase
{
    /** @var RestClientFactoryInterface|MockObject */
    protected $clientFactory;

    /** @var AbstractRestTransport|MockObject */
    protected $transport;

    protected function setUp(): void
    {
        $this->clientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->transport = $this->getMockBuilder(AbstractRestTransport::class)->getMockForAbstractClass();
        $this->transport->setRestClientFactory($this->clientFactory);
    }

    public function testInitWorks()
    {
        $expectedBaseUrl = 'https://example.com/api/v2';
        $expectedClientOptions = ['auth' => ['username', 'password']];

        $expectedClient = $this->createMock(RestClientInterface::class);

        /** @var Transport|MockObject $entity */
        $entity = $this->getMockBuilder(Transport::class)->disableOriginalConstructor()->getMock();

        $settings = $this->createMock(ParameterBag::class);

        $entity->expects(static::atLeastOnce())
            ->method('getSettingsBag')
            ->willReturn($settings);

        $this->transport->expects(static::once())
            ->method('getClientBaseUrl')
            ->with($settings)
            ->willReturn($expectedBaseUrl);

        $this->transport->expects(static::once())
            ->method('getClientOptions')
            ->with($settings)
            ->willReturn($expectedClientOptions);

        $this->clientFactory->expects(static::once())
            ->method('createRestClient')
            ->with($expectedBaseUrl, $expectedClientOptions)
            ->willReturn($expectedClient);

        $this->transport->init($entity);

        static::assertSame($expectedClient, $this->transport->getClient());
        static::assertSame($expectedClient, $this->transport->getClient());
    }

    public function testGetClientFails()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("REST Transport isn't configured properly.");

        $this->transport->getClient();
    }
}
