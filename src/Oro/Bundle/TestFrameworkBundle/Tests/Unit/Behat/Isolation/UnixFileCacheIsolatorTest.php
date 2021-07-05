<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\UnixFileCacheIsolator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UnixFileCacheIsolatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider onlyFileHandlerIsApplicableProvider
     */
    public function testOnlyFileHandlerIsApplicable(string $sessionHandlerParameter, bool $expectedResult)
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->any())
            ->method('getParameter')
            ->willReturn($sessionHandlerParameter);
        $isolatorMock = $this->getMockBuilder(UnixFileCacheIsolator::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isApplicableOS'])
            ->getMock();
        $isolatorMock->expects($this->any())
            ->method('isApplicableOS')
            ->willReturn(true);

        self::assertSame($expectedResult, $isolatorMock->isApplicable($containerMock));
    }

    public function onlyFileHandlerIsApplicableProvider(): array
    {
        return [
            [
                'session_handler' => 'session.handler.native_file',
                'expected result' => true,
            ],
            [
                'session_handler' => 'snc_redis.session.handler',
                'expected result' => true,
            ],
        ];
    }
}
