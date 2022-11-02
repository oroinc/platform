<?php

namespace Oro\Bundle\LayoutBundle\Tests\Unit\Layout\Serializer;

use Oro\Bundle\LayoutBundle\Layout\Serializer\BlockViewVarsNormalizer;

class BlockViewVarsNormalizerTest extends \PHPUnit\Framework\TestCase
{
    /** @var BlockViewVarsNormalizer */
    private $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BlockViewVarsNormalizer();
    }

    /**
     * @dataProvider normalizeDataProvider
     */
    public function testNormalize(array $vars, array $normalizedVars): void
    {
        $this->normalizer->normalize($vars, []);
        self::assertEquals($normalizedVars, $vars);
    }

    /**
     * @dataProvider denormalizeDataProvider
     */
    public function testDenormalize(array $vars, array $denormalizedVars): void
    {
        $this->normalizer->denormalize($vars, ['context_hash' => 'test_context_hash']);
        self::assertEquals($denormalizedVars, $vars);
    }

    public function normalizeDataProvider(): array
    {
        return [
            'required vars only(null cache)' => [
                'vars'           => [
                    'option1'    => 'value1',
                    'id'         => 'test_id',
                    'block_type' => 'test_type',
                    'cache'      => null
                ],
                'normalizedVars' => [
                    'option1'    => 'value1',
                    'id'         => 'test_id',
                    'block_type' => 'test_type'
                ]
            ],
            'default values(empty cache)'    => [
                'vars'           => [
                    'option1'              => 'value1',
                    'id'                   => 'test_id',
                    'block_type'           => 'test_type',
                    'visible'              => true,
                    'hidden'               => false,
                    'attr'                 => [],
                    'translation_domain'   => 'messages',
                    'class_prefix'         => null,
                    'block_type_widget_id' => 'test_type_widget',
                    'unique_block_prefix'  => '_test_id',
                    'cache_key'            => '_test_id_test_type_test_context_hash',
                    'cache'                => ''
                ],
                'normalizedVars' => [
                    'option1'    => 'value1',
                    'id'         => 'test_id',
                    'block_type' => 'test_type',
                    'cache'      => ''
                ]
            ],
            'all vars(not empty cache)'      => [
                'vars'           => [
                    'option1'              => 'value1',
                    'id'                   => 'test_id',
                    'block_type'           => 'test_type',
                    'visible'              => false,
                    'hidden'               => true,
                    'attr'                 => ['attr1' => 'val1'],
                    'translation_domain'   => 'test_domain',
                    'class_prefix'         => 'test_prefix',
                    'block_type_widget_id' => 'test_type_widget',
                    'unique_block_prefix'  => '_test_id',
                    'cache_key'            => '_test_id_test_type_test_context_hash',
                    'cache'                => 'test'
                ],
                'normalizedVars' => [
                    'option1'            => 'value1',
                    'id'                 => 'test_id',
                    'block_type'         => 'test_type',
                    'visible'            => false,
                    'hidden'             => true,
                    'attr'               => ['attr1' => 'val1'],
                    'translation_domain' => 'test_domain',
                    'class_prefix'       => 'test_prefix',
                    'cache'              => 'test'
                ]
            ]
        ];
    }

    public function denormalizeDataProvider(): array
    {
        return [
            'required vars only(null cache)' => [
                'vars'             => [
                    'option1'    => 'value1',
                    'id'         => 'test_id',
                    'block_type' => 'test_type'
                ],
                'denormalizedVars' => [
                    'option1'              => 'value1',
                    'id'                   => 'test_id',
                    'block_type'           => 'test_type',
                    'visible'              => true,
                    'hidden'               => false,
                    'attr'                 => [],
                    'translation_domain'   => 'messages',
                    'class_prefix'         => null,
                    'block_type_widget_id' => 'test_type_widget',
                    'unique_block_prefix'  => '_test_id',
                    'cache_key'            => '_test_id_test_type_test_context_hash',
                    'cache'                => null
                ]
            ],
            'required vars only(empty cache)' => [
                'vars'             => [
                    'option1'    => 'value1',
                    'id'         => 'test_id',
                    'block_type' => 'test_type',
                    'cache'      => ''
                ],
                'denormalizedVars' => [
                    'option1'              => 'value1',
                    'id'                   => 'test_id',
                    'block_type'           => 'test_type',
                    'visible'              => true,
                    'hidden'               => false,
                    'attr'                 => [],
                    'translation_domain'   => 'messages',
                    'class_prefix'         => null,
                    'block_type_widget_id' => 'test_type_widget',
                    'unique_block_prefix'  => '_test_id',
                    'cache_key'            => '_test_id_test_type_test_context_hash',
                    'cache'                => ''
                ]
            ],
            'all vars(not empty cache)'           => [
                'vars'             => [
                    'option1'            => 'value1',
                    'id'                 => 'test_id',
                    'block_type'         => 'test_type',
                    'visible'            => false,
                    'hidden'             => true,
                    'attr'               => ['attr1' => 'val1'],
                    'translation_domain' => 'test_domain',
                    'class_prefix'       => 'test_prefix',
                    'cache'              => 'test'
                ],
                'denormalizedVars' => [
                    'option1'              => 'value1',
                    'id'                   => 'test_id',
                    'block_type'           => 'test_type',
                    'visible'              => false,
                    'hidden'               => true,
                    'attr'                 => ['attr1' => 'val1'],
                    'translation_domain'   => 'test_domain',
                    'class_prefix'         => 'test_prefix',
                    'block_type_widget_id' => 'test_type_widget',
                    'unique_block_prefix'  => '_test_id',
                    'cache_key'            => '_test_id_test_type_test_context_hash',
                    'cache'                => 'test'
                ]
            ],
        ];
    }
}
