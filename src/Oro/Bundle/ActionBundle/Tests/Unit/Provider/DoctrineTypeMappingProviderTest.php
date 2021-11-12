<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Component\Action\Model\DoctrineTypeMappingExtensionInterface;

class DoctrineTypeMappingProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider doctrineTypesProvider
     */
    public function testGetDoctrineTypeMappings(array $extensions, array $expected)
    {
        $provider = new DoctrineTypeMappingProvider($extensions);
        $this->assertEquals($expected, $provider->getDoctrineTypeMappings());
    }

    public function doctrineTypesProvider(): array
    {
        $extension1 = $this->createExtension();
        $extension2 = $this->createExtension(
            ['test_type' => ['type' => 'test_type', 'options' => []]]
        );
        $extension3 = $this->createExtension(
            ['test_type2' => ['type' => 'test_type2', 'options' => []]]
        );

        return [
            'no extension'              => [
                'extensions' => [],
                'expected'   => []
            ],
            'one extension'             => [
                'extensions' => [$extension2],
                'expected'   => [
                    'test_type' => ['type' => 'test_type', 'options' => []]
                ]
            ],
            'two extensions'            => [
                'extensions' => [$extension1, $extension2],
                'expected'   => [
                    'test_type' => ['type' => 'test_type', 'options' => []]
                ]
            ],
            'test two extensions merge' => [
                'extensions' => [$extension1, $extension2, $extension3],
                'expected'   => [
                    'test_type'  => ['type' => 'test_type', 'options' => []],
                    'test_type2' => ['type' => 'test_type2', 'options' => []]
                ]
            ]
        ];
    }

    private function createExtension(array $types = []): DoctrineTypeMappingExtensionInterface
    {
        $extension = $this->createMock(DoctrineTypeMappingExtensionInterface::class);
        $extension->expects($this->any())
            ->method('getDoctrineTypeMappings')
            ->willReturn($types);

        return $extension;
    }
}
