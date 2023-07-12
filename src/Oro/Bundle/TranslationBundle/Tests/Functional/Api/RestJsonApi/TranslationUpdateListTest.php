<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures\LoadTranslations;

/**
 * @dbIsolationPerTest
 */
class TranslationUpdateListTest extends RestJsonApiUpdateListTestCase
{
    use ResolveTranslationIdTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testUpdateEntitiesById(): void
    {
        $existingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $notExistingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>');
        $this->processUpdateList(
            TranslationKey::class,
            [
                'data' => [
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'translations',
                        'id'         => $existingTranslationId,
                        'attributes' => ['translatedValue' => 'updated trans1 (en_CA)']
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'translations',
                        'id'         => $notExistingTranslationId,
                        'attributes' => ['translatedValue' => 'updated trans1 (en_US)']
                    ]
                ]
            ]
        );

        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter' => ['id' => [$existingTranslationId, $notExistingTranslationId]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => $existingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_CA',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_CA)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => $notExistingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_US',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_US)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_US)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateEntitiesByDomainKeyAndLanguageCode(): void
    {
        $existingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $notExistingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>');
        $this->processUpdateList(
            TranslationKey::class,
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_CA',
                            'translatedValue' => 'updated trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_US',
                            'translatedValue' => 'updated trans1 (en_US)'
                        ]
                    ]
                ]
            ]
        );

        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter' => ['id' => [$existingTranslationId, $notExistingTranslationId]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => $existingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_CA',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_CA)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => $notExistingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_US',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_US)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_US)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateEntitiesByIdAndByDomainKeyAndLanguageCode(): void
    {
        $existingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $notExistingTranslationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>');
        $this->processUpdateList(
            TranslationKey::class,
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_CA',
                            'translatedValue' => 'updated trans1 (en_CA)'
                        ]
                    ],
                    [
                        'meta'       => ['update' => true],
                        'type'       => 'translations',
                        'id'         => $notExistingTranslationId,
                        'attributes' => ['translatedValue' => 'updated trans1 (en_US)']
                    ]
                ]
            ]
        );

        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter' => ['id' => [$existingTranslationId, $notExistingTranslationId]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => $existingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_CA',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_CA)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => $notExistingTranslationId,
                        'attributes' => [
                            'domain'          => 'test_domain',
                            'key'             => 'test.trans1',
                            'languageCode'    => 'en_US',
                            'hasTranslation'  => true,
                            'value'           => 'updated trans1 (en_US)',
                            'englishValue'    => 'test trans1 (en)',
                            'translatedValue' => 'updated trans1 (en_US)'
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
