<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Provider\DoctrineTypeMappingProvider;
use Oro\Component\Action\Model\DoctrineTypeMappingExtensionInterface;

class DoctrineTypeMappingProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var DoctrineTypeMappingProvider */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->provider = new DoctrineTypeMappingProvider();
    }

    /**
     * @dataProvider doctrineTypesProvider
     *
     * @param DoctrineTypeMappingExtensionInterface[] $extensions
     * @param array $expected
     */
    public function testGetDoctrineTypeMappings($extensions, array $expected)
    {
        foreach ($extensions as $extension) {
            $this->provider->addExtension($extension);
        }

        $this->assertEquals(
            $expected,
            $this->provider->getDoctrineTypeMappings()
        );
    }

    /**
     * @return \Generator
     */
    public function doctrineTypesProvider()
    {
        $extension1 = $this->createExtension();
        $extension2 = $this->createExtension(
            ['test_type' => ['type' => 'test_type', 'options' => []]]
        );
        $extension3 = $this->createExtension(
            ['test_type2' => ['type' => 'test_type2', 'options' => []]]
        );


        yield 'no extension' => [
            'extensions' => [],
            'expected' => []
        ];

        yield 'one extension' => [
            'extensions' => [$extension2],
            'expected' => [
                'test_type' => ['type' => 'test_type', 'options' => []]
            ]
        ];

        yield 'two extensions' => [
            'extensions' => [$extension1, $extension2],
            'expected' => [
                'test_type' => ['type' => 'test_type', 'options' => []]
            ]
        ];

        yield 'test two extensions merge' => [
            'extensions' => [$extension1, $extension2, $extension3],
            'expected' => [
                'test_type' => ['type' => 'test_type', 'options' => []],
                'test_type2' => ['type' => 'test_type2', 'options' => []]
            ]
        ];
    }

    /**
     * @param array $types
     *
     * @return DoctrineTypeMappingExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createExtension(array $types = [])
    {
        $extension = $this->createMock(DoctrineTypeMappingExtensionInterface::class);
        $extension->expects($this->any())->method('getDoctrineTypeMappings')->willReturn($types);

        return $extension;
    }
}
