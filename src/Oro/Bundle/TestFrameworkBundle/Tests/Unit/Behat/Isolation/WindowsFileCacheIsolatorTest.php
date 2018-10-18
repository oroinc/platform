<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\WindowsFileCacheIsolator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WindowsFileCacheIsolatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider onlyFileHandlerIsApplicableProvider
     * @param string $sessionHandlerParameter
     * @param boolean $expectedResult
     */
    public function testOnlyFileHandlerIsApplicable($sessionHandlerParameter, $expectedResult)
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->method('getParameter')->willReturn($sessionHandlerParameter);
        $isolatorMock = $this->getMockBuilder(WindowsFileCacheIsolator::class)
            ->disableOriginalConstructor()
            ->setMethods(['isApplicableOS'])
            ->getMock();
        $isolatorMock->method('isApplicableOS')->willReturn(true);

        self::assertSame($expectedResult, $isolatorMock->isApplicable($containerMock));
    }

    public function onlyFileHandlerIsApplicableProvider()
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
