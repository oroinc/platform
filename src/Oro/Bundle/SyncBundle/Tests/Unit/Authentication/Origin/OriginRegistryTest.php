<?php

namespace Oro\Bundle\SyncBundle\Tests\Unit\Authentication\Origin;

use Oro\Bundle\SyncBundle\Authentication\Origin\OriginProviderInterface;
use Oro\Bundle\SyncBundle\Authentication\Origin\OriginRegistry;

class OriginRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var OriginProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $originProvider;

    /** @var OriginRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $originRegistry;

    protected function setUp(): void
    {
        $this->originProvider = $this->createMock(OriginProviderInterface::class);

        $this->originRegistry = new OriginRegistry($this->originProvider);
    }

    /**
     * @dataProvider getOriginsDataProvider
     */
    public function testGetOrigins(array $dynamicOrigins, array $configuredOrigins, array $expectedOrigins): void
    {
        $this->originProvider->expects(self::once())
            ->method('getOrigins')
            ->willReturn($dynamicOrigins);

        foreach ($configuredOrigins as $configuredOrigin) {
            $this->originRegistry->addOrigin($configuredOrigin);
        }

        self::assertEquals($expectedOrigins, $this->originRegistry->getOrigins());
    }

    /**
     * @return array
     */
    public function getOriginsDataProvider(): array
    {
        return [
            'no dynamic origins'                   => [
                'dynamicOrigins'    => [],
                'configuredOrigins' => ['sampleOrigin3', 'sampleOrigin4'],
                'expectedOrigins'   => ['sampleOrigin3', 'sampleOrigin4'],
            ],
            'no configured origins'                => [
                'dynamicOrigins'    => ['sampleOrigin1', 'sampleOrigin2'],
                'configuredOrigins' => [],
                'expectedOrigins'   => ['sampleOrigin1', 'sampleOrigin2'],
            ],
            'ensure origins are merged'            => [
                'dynamicOrigins'    => ['sampleOrigin1', 'sampleOrigin2'],
                'configuredOrigins' => ['sampleOrigin3', 'sampleOrigin4'],
                'expectedOrigins'   => ['sampleOrigin3', 'sampleOrigin4', 'sampleOrigin1', 'sampleOrigin2'],
            ],
            'ensure duplicated origin are removed' => [
                'dynamicOrigins'    => ['sampleOrigin1', 'sampleOrigin2'],
                'configuredOrigins' => ['sampleOrigin2', 'sampleOrigin3'],
                'expectedOrigins'   => ['sampleOrigin2', 'sampleOrigin3', 'sampleOrigin1'],
            ],
        ];
    }
}
