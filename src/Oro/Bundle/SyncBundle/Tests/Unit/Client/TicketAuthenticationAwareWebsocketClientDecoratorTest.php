<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Client;

use Oro\Bundle\SyncBundle\Authentication\Ticket\TicketProviderInterface;
use Oro\Bundle\SyncBundle\Client\WebsocketClientInterface;
use Oro\Bundle\SyncBundle\Client\TicketAuthenticationAwareWebsocketClientDecorator;

class TicketAuthenticationAwareWebsocketClientDecoratorTest extends \PHPUnit_Framework_TestCase
{
    private const TICKET = 'sampleTicket';

    /**
     * @var WebsocketClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $decoratedClient;

    /**
     * @var TicketProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $ticketProvider;

    /**
     * @var TicketAuthenticationAwareWebsocketClientDecorator
     */
    private $ticketAuthenticationAwareClientDecorator;

    protected function setUp()
    {
        $this->decoratedClient = $this->createMock(WebsocketClientInterface::class);
        $this->ticketProvider = $this->createMock(TicketProviderInterface::class);

        $this->ticketAuthenticationAwareClientDecorator = new TicketAuthenticationAwareWebsocketClientDecorator(
            $this->decoratedClient,
            $this->ticketProvider
        );
    }

    /**
     * @dataProvider connectDataProvider
     *
     * @param string $target
     * @param string $expectedTarget
     */
    public function testConnect(string $target, string $expectedTarget)
    {
        $connectionSession = 'sampleSession';

        $this->decoratedClient
            ->expects(self::once())
            ->method('connect')
            ->with($expectedTarget)
            ->willReturn($connectionSession);

        $this->ticketProvider
            ->expects(self::once())
            ->method('generateTicket')
            ->with(true)
            ->willReturn(self::TICKET);

        self::assertSame($connectionSession, $this->ticketAuthenticationAwareClientDecorator->connect($target));
    }

    /**
     * @return array
     */
    public function connectDataProvider(): array
    {
        return [
            'empty path in target' => [
                'target' => '',
                'expectedTarget' => '?ticket=' . self::TICKET,
            ],

            'root target' => [
                'target' => '/',
                'expectedTarget' => '/?ticket=' . self::TICKET,
            ],

            'normal path' => [
                'target' => '/sample-path',
                'expectedTarget' => '/sample-path?ticket=' . self::TICKET,
            ],

            'normal path with query' => [
                'target' => '/sample-path?fooParam=bar',
                'expectedTarget' => '/sample-path?fooParam=bar&ticket=' . self::TICKET,
            ],
        ];
    }
}
