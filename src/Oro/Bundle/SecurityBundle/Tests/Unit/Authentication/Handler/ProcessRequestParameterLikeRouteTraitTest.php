<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Authentication\Handler;

use Oro\Bundle\SecurityBundle\Tests\Unit\Stub\ProcessRequestParameterLikeRouteTraitStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

class ProcessRequestParameterLikeRouteTraitTest extends TestCase
{
    private const REQUEST_PARAMETER = 'request_parameter';

    private MockObject|LoggerInterface $logger;
    private ProcessRequestParameterLikeRouteTraitStub $handler;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->handler = new ProcessRequestParameterLikeRouteTraitStub($this->logger);
    }

    /**
     * @dataProvider processRequestDataProvider
     */
    public function testProcessRequestParameter(?string $expected, ?string $actual)
    {
        if (null === $expected) {
            $this->logger->expects(self::once())
                ->method('debug')
                ->with('Request parameter is removed because it looks like route name.', [
                    'parameter' => self::REQUEST_PARAMETER,
                    'value' => $actual,
                ]);
        }

        $request = Request::create('/uri', Request::METHOD_POST, [self::REQUEST_PARAMETER => $actual]);
        $this->handler->processRequestParameterPublic($request, self::REQUEST_PARAMETER);
        self::assertEquals($expected, $request->get(self::REQUEST_PARAMETER));
    }

    public function processRequestDataProvider(): \Generator
    {
        yield ['some-uri', 'some-uri'];
        yield ['uri', 'uri'];
        yield ['/uri', '/uri'];
        yield ['http://example.com', 'http://example.com'];
        yield ['https://example.com/some-path', 'https://example.com/some-path'];
        yield ['https://example.log/some_path', 'https://example.log/some_path'];
        yield [null, 'admin_users'];
        yield [null, 'Admin_users'];
        yield [null, 'ADMIN_USERS_CREATE'];
    }
}
