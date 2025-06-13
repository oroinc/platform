<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Converter\ComplexData\Mapping;

use Oro\Bundle\ImportExportBundle\Converter\ComplexData\Mapping\ComplexDataStaticMappingProvider;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\FirstTestBundle\FirstTestBundle;
use Oro\Bundle\ImportExportBundle\Tests\Unit\Fixtures\SecondTestBundle\SecondTestBundle;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use PHPUnit\Framework\TestCase;

class ComplexDataStaticMappingProviderTest extends TestCase
{
    use TempDirExtension;

    private ComplexDataStaticMappingProvider $mappingProvider;

    #[\Override]
    protected function setUp(): void
    {
        $bundle1 = new FirstTestBundle();
        $bundle2 = new SecondTestBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2),
            ]);

        $this->mappingProvider = new ComplexDataStaticMappingProvider(
            'complex_data_import_mapping',
            'Resources/config/oro/complex_data_import.yml',
            $this->getTempFile('ComplexDataStaticMappingProvider'),
            false
        );
    }

    public function testGetMapping(): void
    {
        self::assertSame(
            [
                'test_entity' => [
                    'target_type' => 'orders',
                    'fields' => [
                        'name' => [
                            'target_path' => 'attributes.name'
                        ],
                        'external' => [
                            'target_path' => 'attributes.external',
                            'value' => true
                        ],
                        'user' => [
                            'target_path' => 'relationships.user.data',
                            'ref' => 'user'
                        ],
                        'lineItems' => [
                            'target_path' => 'relationships.lineItems.data',
                            'ref' => 'line_items'
                        ],
                        'externalIdentifier' => [
                            'target_path' => 'attributes.externalIdentifier'
                        ]
                    ]
                ],
                'user' => [
                    'target_type' => 'users',
                    'entity' => 'Entity\User',
                    'lookup_field' => 'email',
                    'ignore_not_found' => true
                ],
                'line_items' => [
                    'target_type' => 'orderlineitems',
                    'collection' => true,
                    'fields' => [
                        'value' => [
                            'target_path' => 'attributes.value'
                        ],
                        'productSku' => [
                            'target_path' => 'attributes.productSku'
                        ],
                        'product' => [
                            'target_path' => 'relationships.product.data',
                            'source' => 'productSku'
                        ]
                    ]
                ]
            ],
            $this->mappingProvider->getMapping([])
        );
    }
}
