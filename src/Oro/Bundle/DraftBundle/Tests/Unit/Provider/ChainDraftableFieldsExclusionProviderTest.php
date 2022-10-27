<?php

namespace Oro\Bundle\DraftBundle\Tests\Unit\Provider;

use Oro\Bundle\DraftBundle\Provider\ChainDraftableFieldsExclusionProvider;
use Oro\Bundle\DraftBundle\Provider\DraftableFieldsExclusionProviderInterface;

class ChainDraftableFieldsExclusionProviderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetExcludedFieldsNoProviders(): void
    {
        $chainProvider = new ChainDraftableFieldsExclusionProvider([]);

        $this->assertEquals([], $chainProvider->getExcludedFields(\stdClass::class));
    }

    public function testGetExcludedFields(): void
    {
        $provider1 = $this->getProviderMock(\stdClass::class, true, 1, ['field_1']);
        $provider2 = $this->getProviderMock(\stdClass::class, false, 0, ['field_2']);
        $provider3 = $this->getProviderMock(\stdClass::class, true, 1, ['field_3']);

        $chainProvider = new ChainDraftableFieldsExclusionProvider([
            $provider1,
            $provider2,
            $provider3
        ]);

        $this->assertEquals(['field_1', 'field_3'], $chainProvider->getExcludedFields(\stdClass::class));
    }

    public function testGetExcludedFieldsWhenAllProvidersDidNotReturnFields(): void
    {
        $provider1 = $this->getProviderMock(\stdClass::class, true, 1, []);

        $chainProvider = new ChainDraftableFieldsExclusionProvider([
            $provider1,
        ]);

        $this->assertEquals([], $chainProvider->getExcludedFields(\stdClass::class));
    }

    /**
     * @param string $className
     * @param bool $isSupport
     * @param int $getExcludedFieldsCalls
     * @param array $excludedFields
     * @return DraftableFieldsExclusionProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getProviderMock(
        string $className,
        bool $isSupport,
        int $getExcludedFieldsCalls,
        array $excludedFields
    ) {
        $provider = $this->createMock(DraftableFieldsExclusionProviderInterface::class);
        $provider->expects($this->once())
            ->method('isSupport')
            ->with($className)
            ->willReturn($isSupport);
        $provider->expects($this->exactly($getExcludedFieldsCalls))
            ->method('getExcludedFields')
            ->willReturn($excludedFields);

        return $provider;
    }
}
