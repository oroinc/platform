<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApiDoc;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures\LoadTranslations;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\PhpUtils\ArrayUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class TranslationTest extends RestJsonApiTestCase
{
    use ResolveTranslationIdTrait;
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    protected function normalizeYmlTemplate(array &$data, array $idReferences): void
    {
        parent::normalizeYmlTemplate($data, $idReferences);
        if (isset($data[JsonApiDoc::TYPE], $data[JsonApiDoc::ID]) && 'translations' === $data[JsonApiDoc::TYPE]) {
            $translationKeyIdKey = $this->getEntityType(TranslationKey::class)
                . '::' . TranslationIdUtil::extractTranslationKeyId($data[JsonApiDoc::ID]);
            $languageCode = TranslationIdUtil::extractLanguageCode($data[JsonApiDoc::ID]);
            if (isset($idReferences[$translationKeyIdKey]) && $this->hasReference($languageCode)) {
                [$translationKeyIdReferenceId, $translationKeyIdFieldName] = $idReferences[$translationKeyIdKey];
                $data[JsonApiDoc::ID] = sprintf(
                    '<@%s->%s>-<@%s->code>',
                    $translationKeyIdReferenceId,
                    $translationKeyIdFieldName,
                    $languageCode
                );
            }
        }
    }

    protected function getResponseData(array|string $expectedContent): array
    {
        $data = parent::getResponseData($expectedContent);
        if (isset($data[JsonApiDoc::DATA])) {
            $resolver = self::getReferenceResolver();
            if (!ArrayUtil::isAssoc($data[JsonApiDoc::DATA])) {
                foreach ($data[JsonApiDoc::DATA] as &$item) {
                    if ('translations' === $item[JsonApiDoc::TYPE]) {
                        $item[JsonApiDoc::ID] = $this->resolveTranslationId($item[JsonApiDoc::ID], $resolver);
                    }
                }
            } elseif ($data[JsonApiDoc::DATA] && 'translations' === $data[JsonApiDoc::DATA][JsonApiDoc::TYPE]) {
                $data[JsonApiDoc::DATA][JsonApiDoc::ID] = $this->resolveTranslationId(
                    $data[JsonApiDoc::DATA][JsonApiDoc::ID],
                    $resolver
                );
            }
        }

        return $data;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'translations']);
        self::assertResponseCount(10, $response);
    }

    public function testGetListFilterById(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[id]' => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_id.yml', $response);
        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterBySeveralIds(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'filter' => [
                    'id' => [
                        $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>'),
                        $this->resolveTranslationId('<@tk-another_trans1-test_domain->id>-<@fr_FR->code>')
                    ]
                ]
            ],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_several_ids.yml', $response);
        self::assertEquals(2, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByDomain(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_domain.yml', $response);
        self::assertEquals(12, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByKey(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[key]' => 'test.trans1'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_key.yml', $response);
        self::assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterBySeveralKeys(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter' => ['key' => ['test.trans1', 'another.trans1']]],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_several_keys.yml', $response);
        self::assertEquals(8, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByKeyWithStartsWithOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[key][starts_with]' => 'another.'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_key_starts_with.yml', $response);
        self::assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByKeyWithNotStartsWithOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[key][not_starts_with]' => 'another.', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_key_not_starts_with.yml', $response);
        self::assertEquals(8, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByHasTranslationTrue(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[hasTranslation]' => 'yes', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_has_trans_true.yml', $response);
        self::assertEquals(5, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByHasTranslationFalse(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[hasTranslation]' => 'no', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_has_trans_false.yml', $response);
        self::assertEquals(7, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[languageCode]' => 'en_CA', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_language.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterBySeveralLanguageCodes(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter' => ['languageCode' => ['en_CA', 'fr_FR']], 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_several_languages.yml', $response);
        self::assertEquals(6, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByLanguageCodeWithNotEqualsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[languageCode][neq]' => 'en_CA', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_language_neq.yml', $response);
        self::assertEquals(9, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByPredefinedLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[languageCode]' => 'current', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_predefined_language.yml', $response);
        self::assertEquals(3, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByUnknownPredefinedLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[languageCode]' => 'unknown', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        self::assertResponseCount(0, $response);
        self::assertEquals(0, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListWithoutAttributesAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => '',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'translations',
                        'id'   => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>'
                    ],
                    [
                        'type' => 'translations',
                        'id'   => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>'
                    ],
                    [
                        'type' => 'translations',
                        'id'   => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseContext['data']);
    }

    public function testGetListOnlyKeyAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'key',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'key' => 'test.trans1'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'key' => 'test.trans2'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'key' => 'another.trans1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyDomainAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'domain',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'domain' => 'test_domain'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'domain' => 'test_domain'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'domain' => 'test_domain'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyHasTranslationAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'hasTranslation',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'hasTranslation' => true
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'hasTranslation' => true
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'hasTranslation' => false
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyLanguageCodeAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'languageCode',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'languageCode' => 'en_CA'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'languageCode' => 'en_CA'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'languageCode' => 'en_CA'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyValueAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'value',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'value' => 'test trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'value' => 'test trans2 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'value' => 'another.trans1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyEnglishValueAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'englishValue',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'englishValue' => 'test trans1 (en)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'englishValue' => 'test.trans2'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'englishValue' => 'another.trans1'
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListOnlyTranslatedValueAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            [
                'fields[translations]' => 'translatedValue',
                'filter[languageCode]' => 'en_CA',
                'filter[domain]'       => 'test_domain'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'translatedValue' => 'test trans1 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-test_trans2-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'translatedValue' => 'test trans2 (en_CA)'
                        ]
                    ],
                    [
                        'type'       => 'translations',
                        'id'         => '<@tk-another_trans1-test_domain->id>-<@en_CA->code>',
                        'attributes' => [
                            'translatedValue' => null
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data'][0]['attributes']);
    }

    public function testGetListFilterByTranslatedValueAndFilterByLanguageCode(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[translatedValue]' => 'test trans1 (en)', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_trans_value.yml', $response);
        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByTranslatedValueWithContainsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[translatedValue][contains]' => 'test', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_trans_value_contains.yml', $response);
        self::assertEquals(4, $response->headers->get('X-Include-Total-Count'));
    }

    public function testGetListFilterByTranslatedValueWithNotContainsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'translations'],
            ['filter[translatedValue][not_contains]' => 'test', 'filter[domain]' => 'test_domain'],
            ['HTTP_X-Include' => 'totalCount']
        );
        $this->assertResponseContains('cget_translation_filter_by_trans_value_not_contains.yml', $response);
        self::assertEquals(1, $response->headers->get('X-Include-Total-Count'));
    }

    /**
     * @dataProvider sortDataProvider
     */
    public function testGetListSort(string $sort, array $ids, array $resultIds): void
    {
        $resolvedIds = [];
        foreach ($ids as $id) {
            $resolvedIds[] = $this->resolveTranslationId($id);
        }
        $result = [];
        foreach ($resultIds as $id) {
            $result[] = ['type' => 'translations', 'id' => $id];
        }
        $response = $this->cget(
            ['entity' => 'translations'],
            ['sort' => $sort, 'filter' => ['id' => $resolvedIds]]
        );
        $this->assertResponseContains(['data' => $result], $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function sortDataProvider(): array
    {
        return [
            'id ASC'              => [
                'id',
                [
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ]
            ],
            'id DESC'             => [
                '-id',
                [
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                ]
            ],
            'key ASC'             => [
                'key',
                [
                    '<@tk-test_trans2-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans2-test_domain->id>-<@fr_FR->code>',
                ]
            ],
            'key DESC'            => [
                '-key',
                [
                    '<@tk-test_trans2-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-test_trans2-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ]
            ],
            'hasTranslation ASC'  => [
                'hasTranslation',
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ]
            ],
            'hasTranslation DESC' => [
                '-hasTranslation',
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ],
                [
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                ]
            ],
            'languageCode ASC'    => [
                'languageCode',
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                ],
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                ]
            ],
            'languageCode DESC'   => [
                '-languageCode',
                [
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                ],
                [
                    '<@tk-another_trans1-test_domain->id>-<@fr_FR->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_US->code>',
                    '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                ]
            ],
        ];
    }

    public function testGet(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ]);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'key'             => 'test.trans1',
                        'domain'          => 'test_domain',
                        'hasTranslation'  => true,
                        'languageCode'    => 'en_CA',
                        'value'           => 'test trans1 (en_CA)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => 'test trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetWithoutAttributes(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => '']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'translations',
                    'id'   => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>'
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('attributes', $responseContext['data']);
    }

    public function testGetOnlyKey(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'key']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'key' => 'test.trans1'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyDomain(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'domain']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'domain' => 'test_domain'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyHasTranslation(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'hasTranslation']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'hasTranslation' => true
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyLanguageCode(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'languageCode']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'languageCode' => 'en_CA'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyValue(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'value']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'value' => 'test trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyEnglishValue(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'englishValue']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'englishValue' => 'test trans1 (en)'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testGetOnlyTranslatedValue(): void
    {
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], ['fields[translations]' => 'translatedValue']);
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => '<@tk-test_trans1-test_domain->id>-<@en_CA->code>',
                    'attributes' => [
                        'translatedValue' => 'test trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
        $responseContext = self::jsonToArray($response->getContent());
        self::assertCount(1, $responseContext['data']['attributes']);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'translations', 'id' => '1-en_US'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'translations'],
            ['filter' => ['id' => '1-en_US']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET, PATCH, POST');
    }

    public function testGetListWhenNoTranslateAccessToLanguages(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Language::class,
            ['VIEW' => AccessLevel::GLOBAL_LEVEL, 'TRANSLATE' => AccessLevel::NONE_LEVEL]
        );
        $response = $this->cget(['entity' => 'translations']);
        self::assertResponseCount(10, $response);
    }

    public function testTryToGetListWhenNoViewAccessToLanguages(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Language::class,
            ['VIEW' => AccessLevel::NONE_LEVEL, 'TRANSLATE' => AccessLevel::GLOBAL_LEVEL]
        );
        $response = $this->cget(['entity' => 'translations']);
        self::assertResponseCount(0, $response);
    }

    public function testTryToGetWhenNoViewAccessToLanguages(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Language::class,
            ['VIEW' => AccessLevel::NONE_LEVEL, 'TRANSLATE' => AccessLevel::GLOBAL_LEVEL]
        );
        $response = $this->get([
            'entity' => 'translations',
            'id'     => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>')
        ], [], [], false);
        $this->assertResponseValidationError(
            ['title' => 'access denied exception', 'detail' => 'No access to the entity.'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
