<?php

namespace Oro\Bundle\TestFrameworkBundle\Tests\Unit\Behat\Isolation;

use Oro\Bundle\TestFrameworkBundle\Behat\Isolation\UnixFileCacheIsolator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UnixFileCacheIsolatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider onlyFileHandlerIsApplicableProvider
     */
    public function testOnlyFileHandlerIsApplicable(bool $miltihost, bool $expectedResult)
    {
        $containerMock = $this->createMock(ContainerInterface::class);
        $containerMock->expects($this->any())
            ->method('hasParameter')
            ->willReturnMap([
                ['oro_multi_host.enabled', true],
                ['kernel.debug', true],
            ]);
        $containerMock->expects($this->any())
            ->method('getParameter')
            ->willReturnMap([
                ['oro_multi_host.enabled', $miltihost],
                ['kernel.debug', false],
            ]);
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
                'miltihost' => true,
                'expected result' => true,
            ],
            [
                'miltihost' => false,
                'expected result' => true,
            ]
        ];
    }
}
