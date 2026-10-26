<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Extend\Entity\TestEntity1;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumOptionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionTranslation;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\TranslationBundle\Api\TranslationIdUtil;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Entity\TranslationKey;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EnumOptionTest extends RestJsonApiTestCase
{
    private const string ENUM_CODE = LoadEnumOptionData::ENUM_CODE;
    private const string OPTION_ID = LoadEnumOptionData::OPTION_ID;
    private const string NEW_OPTION_ID = self::ENUM_CODE . '.api_created';
    private const string LANGUAGE_CODE = LoadEnumOptionData::LANGUAGE_CODE;
    private const string FOREIGN_TRANSLATION_KEY = LoadEnumOptionData::FOREIGN_TRANSLATION_KEY;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        self::assertEquals(
            self::ENUM_CODE,
            $this->getFieldConfig('enum', TestEntity1::class, 'testEnumField')->get('enum_code')
        );
        self::assertEquals(
            ExtendScope::OWNER_CUSTOM,
            $this->getFieldConfig('extend', TestEntity1::class, 'testEnumField')->get('owner')
        );

        $this->loadFixtures([LoadEnumOptionData::class]);
    }

    private function getFieldConfig(string $scope, string $className, string $fieldName): ConfigInterface
    {
        /** @var ConfigManager $configManager */
        $configManager = self::getContainer()->get('oro_entity_config.config_manager');

        return $configManager->getFieldConfig($scope, $className, $fieldName);
    }

    private function findEnumOption(string $id): ?EnumOption
    {
        return $this->getEntityManager()->find(EnumOption::class, $id);
    }

    private function getEnumOption(string $id): EnumOption
    {
        $option = $this->findEnumOption($id);
        self::assertNotNull($option);

        return $option;
    }

    private function getLanguage(string $code): Language
    {
        return $this->getEntityManager()->getRepository(Language::class)->findOneBy(['code' => $code]);
    }

    private function findTranslation(
        TranslationKey $translationKey,
        string $languageCode = self::LANGUAGE_CODE
    ): ?Translation {
        return $this->getEntityManager()->getRepository(Translation::class)->findOneBy([
            'translationKey' => $translationKey,
            'language' => $this->getLanguage($languageCode)
        ]);
    }

    private function getTranslation(
        TranslationKey $translationKey,
        string $languageCode = self::LANGUAGE_CODE
    ): Translation {
        $translation = $this->findTranslation($translationKey, $languageCode);
        self::assertNotNull($translation);

        return $translation;
    }

    private function getTranslationKey(string $key): ?TranslationKey
    {
        $translationKey = $this->getEntityManager()->getRepository(TranslationKey::class)->findOneBy([
            'key' => $key,
            'domain' => TranslationManager::DEFAULT_DOMAIN
        ]);
        self::assertNotNull($translationKey);

        return $translationKey;
    }

    private function getEnumOptionTranslationKey(string $optionId = self::OPTION_ID): TranslationKey
    {
        return $this->getTranslationKey(ExtendHelper::buildEnumOptionTranslationKey($optionId));
    }

    private function getAllTranslationsData(TranslationKey $translationKey): array
    {
        return [
            [
                'type' => 'translations',
                'id' => TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en')
            ],
            [
                'type' => 'translations',
                'id' => TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_CA')
            ],
            [
                'type' => 'translations',
                'id' => TranslationIdUtil::buildTranslationId($translationKey->getId(), 'fr_FR')
            ],
            [
                'type' => 'translations',
                'id' => TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_US')
            ]
        ];
    }

    public function testGetOption(): void
    {
        $response = $this->get(['entity' => 'enumoptions', 'id' => self::OPTION_ID]);

        $expectedData = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 0,
                    'default' => false,
                    'enumCode' => self::ENUM_CODE,
                    'optionId' => '2_of_5'
                ]
            ]
        ];
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData(
            $this->getEnumOptionTranslationKey()
        );
        $this->assertResponseContains($expectedData, $response);

        // test that "name" is not exposed
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('name', $responseData['data']['attributes']);
    }

    public function testGetListOfOptions(): void
    {
        $response = $this->cget(
            ['entity' => 'enumoptions'],
            ['filter[enumCode]' => self::ENUM_CODE]
        );

        $expectedData = [
            'data' => [
                [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'attributes' => [
                        'priority' => 0,
                        'default' => false,
                        'enumCode' => self::ENUM_CODE,
                        'optionId' => '2_of_5'
                    ]
                ],
                [
                    'type' => 'enumoptions',
                    'id' => self::ENUM_CODE . '.widgets',
                    'attributes' => [
                        'priority' => 0,
                        'default' => false,
                        'enumCode' => self::ENUM_CODE,
                        'optionId' => 'widgets'
                    ]
                ]
            ]
        ];
        $expectedData['data'][0]['relationships']['names']['data'] = $this->getAllTranslationsData(
            $this->getEnumOptionTranslationKey()
        );
        $expectedData['data'][1]['relationships']['names']['data'] = $this->getAllTranslationsData(
            $this->getEnumOptionTranslationKey(self::ENUM_CODE . '.widgets')
        );
        $this->assertResponseContains($expectedData, $response);

        // test that "name" is not exposed
        $responseData = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('name', $responseData['data'][0]['attributes']);
        self::assertArrayNotHasKey('name', $responseData['data'][1]['attributes']);
    }

    public function testGetOptionWithExpandedNames(): void
    {
        $response = $this->get(['entity' => 'enumoptions', 'id' => self::OPTION_ID], ['include' => 'names']);

        $translationKey = $this->getEnumOptionTranslationKey();
        $translationIdEn = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en');
        $translationIdEnCa = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_CA');
        $translationIdFrFr = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'fr_FR');
        $translationIdEnUs = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_US');
        $expectedData = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 0,
                    'default' => false,
                    'enumCode' => self::ENUM_CODE,
                    'optionId' => '2_of_5'
                ],
                'relationships' => [
                    'names' => [
                        'data' => [
                            ['type' => 'translations', 'id' => $translationIdEn],
                            ['type' => 'translations', 'id' => $translationIdEnCa],
                            ['type' => 'translations', 'id' => $translationIdFrFr],
                            ['type' => 'translations', 'id' => $translationIdEnUs]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'translations',
                    'id' => $translationIdEn,
                    'attributes' => [
                        'languageCode' => 'en',
                        'hasTranslation' => true,
                        'value' => '2 of 5',
                        'englishValue' => '2 of 5',
                        'translatedValue' => '2 of 5'
                    ]
                ],
                [
                    'type' => 'translations',
                    'id' => $translationIdEnCa,
                    'attributes' => [
                        'languageCode' => 'en_CA',
                        'hasTranslation' => false,
                        'value' => '2 of 5',
                        'englishValue' => '2 of 5',
                        'translatedValue' => null
                    ]
                ],
                [
                    'type' => 'translations',
                    'id' => $translationIdFrFr,
                    'attributes' => [
                        'languageCode' => 'fr_FR',
                        'hasTranslation' => true,
                        'value' => '2 sur 5',
                        'englishValue' => '2 of 5',
                        'translatedValue' => '2 sur 5'
                    ]
                ],
                [
                    'type' => 'translations',
                    'id' => $translationIdEnUs,
                    'attributes' => [
                        'languageCode' => 'en_US',
                        'hasTranslation' => false,
                        'value' => '2 of 5',
                        'englishValue' => '2 of 5',
                        'translatedValue' => null
                    ]
                ]
            ]
        ];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testFilterByOptionId(): void
    {
        $response = $this->cget(
            ['entity' => 'enumoptions'],
            ['filter[enumCode]' => self::ENUM_CODE, 'filter[optionId]' => '2_of_5']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'enumoptions',
                        'id' => self::OPTION_ID,
                        'attributes' => [
                            'enumCode' => self::ENUM_CODE,
                            'optionId' => '2_of_5'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateOptionWithoutNames(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 3
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::NEW_OPTION_ID,
                'attributes' => [
                    'priority' => 3,
                    'default' => false,
                    'enumCode' => self::ENUM_CODE,
                    'optionId' => 'api_created'
                ]
            ]
        ];
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData(
            $this->getEnumOptionTranslationKey(self::NEW_OPTION_ID)
        );
        $this->assertResponseContains($expectedData, $response);

        $option = $this->getEnumOption(self::NEW_OPTION_ID);
        self::assertEquals('api_created', $option->getName());
        self::assertEquals(3, $option->getPriority());
    }

    public function testTryToCreateOptionWithPriorityOutsideIntegerRange(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 2147483648
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'range constraint',
                'detail' => 'This value should be between -2147483648 and 2147483647.',
                'source' => ['pointer' => '/data/attributes/priority']
            ],
            $response
        );
    }

    public function testCanCreateOptionWithMaximumIntegerPriority(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 2147483647
                    ]
                ]
            ]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_CREATED);
        self::assertEquals(2147483647, $this->getEnumOption(self::NEW_OPTION_ID)->getPriority());
    }

    public function testTryToUpdateOptionWithPriorityOutsideIntegerRange(): void
    {
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'attributes' => [
                        'priority' => 2147483648
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'range constraint',
                'detail' => 'This value should be between -2147483648 and 2147483647.',
                'source' => ['pointer' => '/data/attributes/priority']
            ],
            $response
        );
        self::assertEquals(0, $this->getEnumOption(self::OPTION_ID)->getPriority());
    }

    public function testTryToCreateOptionWithTooLongTranslationValue(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-fr']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-fr',
                        'attributes' => [
                            'languageCode' => self::LANGUAGE_CODE,
                            'translatedValue' => str_repeat('a', 255)
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'length constraint',
                'detail' => 'This value is too long. It should have 254 characters or less.',
                'source' => ['pointer' => '/included/0/attributes/translatedValue']
            ],
            $response
        );
    }

    public function testTryToUpdateOptionWithTooLongTranslationValue(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), self::LANGUAGE_CODE);
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => $translationId]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => $translationId,
                        'meta' => ['update' => true],
                        'attributes' => [
                            'translatedValue' => str_repeat('a', 255)
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'length constraint',
                'detail' => 'This value is too long. It should have 254 characters or less.',
                'source' => ['pointer' => '/included/0/attributes/translatedValue']
            ],
            $response
        );
        self::assertEquals('2 sur 5', $this->getTranslation($translationKey)->getValue());
    }

    public function testCreateOptionWithTranslations(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 3
                    ],
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-fr'],
                                ['type' => 'translations', 'id' => 'name-en-ca']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-fr',
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Option créée'
                        ]
                    ],
                    [
                        'type' => 'translations',
                        'id' => 'name-en-ca',
                        'attributes' => [
                            'languageCode' => 'en_CA',
                            'translatedValue' => 'Created option in Canada'
                        ]
                    ]
                ]
            ]
        );

        $translationKey = $this->getEnumOptionTranslationKey(self::NEW_OPTION_ID);
        $translationIdEn = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en');
        $translationIdEnCa = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_CA');
        $translationIdFrFr = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'fr_FR');
        $translationIdEnUs = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_US');
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 3
                    ],
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => $translationIdFrFr],
                                ['type' => 'translations', 'id' => $translationIdEnCa],
                                ['type' => 'translations', 'id' => $translationIdEn],
                                ['type' => 'translations', 'id' => $translationIdEnUs]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => $translationIdFrFr,
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Option créée'
                        ]
                    ],
                    [
                        'type' => 'translations',
                        'id' => $translationIdEnCa,
                        'attributes' => [
                            'languageCode' => 'en_CA',
                            'translatedValue' => 'Created option in Canada'
                        ]
                    ]
                ]
            ],
            $response
        );

        self::assertEquals('Option créée', $this->getEnumOption(self::NEW_OPTION_ID)->getName());
        self::assertEquals('Option créée', $this->getTranslation($translationKey)->getValue());
        self::assertEquals('Created option in Canada', $this->getTranslation($translationKey, 'en_CA')->getValue());
        self::assertNull($this->findTranslation($translationKey, 'en'));
        self::assertNull($this->findTranslation($translationKey, 'en_US'));
    }

    public function testCreateOptionWithEnglishTranslation(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 3
                    ],
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-fr'],
                                ['type' => 'translations', 'id' => 'name-en']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-fr',
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Option française'
                        ]
                    ],
                    [
                        'type' => 'translations',
                        'id' => 'name-en',
                        'attributes' => [
                            'languageCode' => 'en',
                            'translatedValue' => 'English option'
                        ]
                    ]
                ]
            ]
        );

        $translationKey = $this->getEnumOptionTranslationKey(self::NEW_OPTION_ID);
        $translationIdEn = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en');
        $translationIdEnCa = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_CA');
        $translationIdFrFr = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'fr_FR');
        $translationIdEnUs = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_US');
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'priority' => 3
                    ],
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => $translationIdFrFr],
                                ['type' => 'translations', 'id' => $translationIdEn],
                                ['type' => 'translations', 'id' => $translationIdEnCa],
                                ['type' => 'translations', 'id' => $translationIdEnUs]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => $translationIdFrFr,
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Option française'
                        ]
                    ],
                    [
                        'type' => 'translations',
                        'id' => $translationIdEn,
                        'attributes' => [
                            'languageCode' => 'en',
                            'translatedValue' => 'English option'
                        ]
                    ]
                ]
            ],
            $response
        );
        self::assertEquals('English option', $this->getEnumOption(self::NEW_OPTION_ID)->getName());
    }

    public function testTryToCreateOptionWithDuplicateTranslation(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-fr'],
                                ['type' => 'translations', 'id' => 'name-fr-duplicate']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-fr',
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Option française'
                        ]
                    ],
                    [
                        'type' => 'translations',
                        'id' => 'name-fr-duplicate',
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Autre nom'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'A translation for this language is already included.',
                'source' => ['pointer' => '/included/1/attributes/languageCode']
            ],
            $response
        );
        self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
    }

    public function testTryToCreateOptionWithUnknownTranslationLanguage(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-unknown']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-unknown',
                        'attributes' => [
                            'languageCode' => 'zz_ZZ',
                            'translatedValue' => 'Unknown language'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'The language is not installed.',
                'source' => ['pointer' => '/included/0/attributes/languageCode']
            ],
            $response
        );
        self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
    }

    public function testTryToCreateOptionWithInvalidValueForTranslationLanguage(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-unknown']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-unknown',
                        'attributes' => [
                            'languageCode' => 123,
                            'translatedValue' => 'Invalid language'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'The language is not installed.',
                'source' => ['pointer' => '/included/0/attributes/languageCode']
            ],
            $response
        );
        self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
    }

    public function testTryToCreateOptionWithForeignTranslationKey(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'name-fr']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'name-fr',
                        'attributes' => [
                            'key' => 'api.enum_option.unrelated_label',
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Should not be saved'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'The translation key and domain are derived from the enum option.',
                'source' => ['pointer' => '/included/0/attributes/key']
            ],
            $response
        );
        self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
        self::assertEquals(
            'Unrelated value',
            $this->getTranslation($this->getTranslationKey(self::FOREIGN_TRANSLATION_KEY))->getValue()
        );
    }

    public function testCreateOptionWithoutTranslations(): void
    {
        $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID
                ]
            ]
        );

        self::assertEquals('api_created', $this->getEnumOption(self::NEW_OPTION_ID)->getName());
        $translationKey = $this->getEnumOptionTranslationKey(self::NEW_OPTION_ID);
        self::assertEquals('api_created', $this->getTranslation($translationKey, 'en')->getValue());
    }

    public function testTryToCreateOptionForUnknownEnum(): void
    {
        $optionId = 'unknown_api_enum.api_created';
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => $optionId
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'enum existence constraint',
                'detail' => 'The enum "unknown_api_enum" does not exist.'
            ],
            $response
        );
        self::assertNull($this->findEnumOption($optionId));
    }

    public function testTryToCreateOptionWithInvalidIdentifier(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => 'invalid_option_id'
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity identifier constraint',
                'detail' => 'Unexpected enum option identifier "invalid_option_id".'
                    . ' Expected a valid "enum_code.option_id" value.',
                'source' => ['pointer' => '/data/id']
            ],
            $response
        );
    }

    public function testTryToCreateDuplicateOption(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'conflict constraint',
                'detail' => 'The entity already exists.'
            ],
            $response,
            Response::HTTP_CONFLICT
        );
    }

    public function testTryToUseUpsertOption(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'meta' => ['upsert' => true]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'The upsert operation is not allowed.',
                'source' => ['pointer' => '/meta/upsert']
            ],
            $response
        );
    }

    public function testTryToCreateOptionWithReadOnlyAttributes(): void
    {
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'enumCode' => 'some_enum_code',
                        'optionId' => 'some_option_id'
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::NEW_OPTION_ID,
                'attributes' => [
                    'priority' => 0,
                    'default' => false,
                    'enumCode' => self::ENUM_CODE,
                    'optionId' => 'api_created'
                ]
            ]
        ];
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData(
            $this->getEnumOptionTranslationKey(self::NEW_OPTION_ID)
        );
        $this->assertResponseContains($expectedData, $response);
        $option = $this->getEnumOption(self::NEW_OPTION_ID);
        self::assertEquals(self::ENUM_CODE, $option->getEnumCode());
        self::assertEquals('api_created', $option->getInternalId());
    }

    public function testTryToCreateOptionForImmutableEnum(): void
    {
        $config = $this->getFieldConfig('enum', TestEntity1::class, 'testEnumField');
        $originalImmutable = $config->get('immutable');
        $config->set('immutable', ['add']);

        try {
            $response = $this->post(
                ['entity' => 'enumoptions'],
                [
                    'data' => [
                        'type' => 'enumoptions',
                        'id' => self::NEW_OPTION_ID
                    ]
                ],
                [],
                false
            );

            $this->assertResponseValidationError(
                [
                    'title' => 'access denied exception',
                    'detail' => 'Options cannot be added to the immutable enum "test_enum_code".'
                ],
                $response,
                Response::HTTP_FORBIDDEN
            );
            self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
        } finally {
            $config->set('immutable', $originalImmutable);
        }
    }

    public function testTryToCreateOptionForSystemEnum(): void
    {
        $config = $this->getFieldConfig('extend', Dashboard::class, 'dashboard_type');
        self::assertEquals(ExtendScope::OWNER_SYSTEM, $config->get('owner'));

        $optionId = 'dashboard_type.api_created';
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => $optionId
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Options cannot be added to the system enum "dashboard_type".'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        self::assertNull($this->findEnumOption($optionId));
    }

    public function testCreateOptionDefaultOptionShouldClearPreviousDefault(): void
    {
        $oldDefault = $this->getEnumOption(self::OPTION_ID);
        $oldDefault->setDefault(true);
        $this->getEntityManager()->flush();

        $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'attributes' => [
                        'default' => true
                    ]
                ]
            ]
        );

        self::assertFalse($this->getEnumOption(self::OPTION_ID)->isDefault());
        self::assertTrue($this->getEnumOption(self::NEW_OPTION_ID)->isDefault());
    }

    public function testUpdateOption(): void
    {
        $data = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 4
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            $data
        );

        $this->assertResponseContains($data, $response);
        $option = $this->getEnumOption(self::OPTION_ID);
        self::assertEquals(4, $option->getPriority());
    }

    public function testTryToUpdateReadOnlyAttributes(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'attributes' => [
                        'enumCode' => 'another_enum',
                        'optionId' => 'another_option_id'
                    ]
                ]
            ]
        );

        $expectedData = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 0,
                    'default' => false,
                    'enumCode' => self::ENUM_CODE,
                    'optionId' => '2_of_5'
                ]
            ]
        ];
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData($translationKey);
        $this->assertResponseContains($expectedData, $response);
        $option = $this->getEnumOption(self::OPTION_ID);
        self::assertEquals(self::ENUM_CODE, $option->getEnumCode());
        self::assertEquals('2_of_5', $option->getInternalId());
    }

    public function testUpdateDefaultOptionShouldClearOtherDefaultsForSameEnum(): void
    {
        $manager = $this->getEntityManager();
        $oldDefault = new EnumOption(self::ENUM_CODE, 'Previous default', 'api_previous_default', 0, true);
        $anotherDefault = new EnumOption(self::ENUM_CODE, 'Another default', 'api_another_default', 0, true);
        $otherEnumDefault = new EnumOption('other_enum', 'Other enum default', 'api_other_default', 0, true);
        $manager->persist($oldDefault);
        $manager->persist($anotherDefault);
        $manager->persist($otherEnumDefault);
        $manager->flush();

        $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'attributes' => [
                        'default' => true
                    ]
                ]
            ]
        );

        self::assertTrue($this->getEnumOption(self::OPTION_ID)->isDefault());
        self::assertFalse($this->getEnumOption($oldDefault->getId())->isDefault());
        self::assertFalse($this->getEnumOption($anotherDefault->getId())->isDefault());
        self::assertTrue($this->getEnumOption($otherEnumDefault->getId())->isDefault());
    }

    public function testUpdateOptionWithoutDefaultShouldNotClearOtherDefaults(): void
    {
        $manager = $this->getEntityManager();
        $oldDefault = new EnumOption(self::ENUM_CODE, 'Previous default', 'api_previous_default', 0, true);
        $manager->persist($oldDefault);
        $manager->flush();

        $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'attributes' => [
                        'priority' => 4
                    ]
                ]
            ]
        );

        self::assertTrue($this->getEnumOption($oldDefault->getId())->isDefault());
    }

    public function testTryToUpdateUnknownOption(): void
    {
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::NEW_OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID
                ]
            ],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testDeleteOption(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationKeyId = $translationKey->getId();

        /** @var Translation[] $translations */
        $translations = $this->getEntityManager()->getRepository(Translation::class)->findBy([
            'translationKey' => $translationKey
        ]);
        $translationIds = [];
        foreach ($translations as $translation) {
            $translationIds[] = $translation->getId();
        }
        self::assertCount(2, $translationIds);

        $enumOptionTranslation = $this->getEntityManager()->getRepository(EnumOptionTranslation::class)->findOneBy([
            'foreignKey' => self::OPTION_ID,
            'locale' => self::LANGUAGE_CODE
        ]);
        self::assertNotNull($enumOptionTranslation);
        $enumOptionTranslationId = $enumOptionTranslation->getId();
        $foreignTranslationKey = $this->getTranslationKey(self::FOREIGN_TRANSLATION_KEY);
        $foreignTranslationId = $this->getTranslation($foreignTranslationKey)->getId();

        $this->delete(['entity' => 'enumoptions', 'id' => self::OPTION_ID]);

        $manager = $this->getEntityManager();
        self::assertNull($this->findEnumOption(self::OPTION_ID));
        self::assertNull($manager->find(TranslationKey::class, $translationKeyId));
        foreach ($translationIds as $translationId) {
            self::assertNull($manager->find(Translation::class, $translationId));
        }
        self::assertNull($manager->find(EnumOptionTranslation::class, $enumOptionTranslationId));
        self::assertNotNull($this->findEnumOption(self::ENUM_CODE . '.widgets'));
        self::assertNotNull($manager->find(TranslationKey::class, $foreignTranslationKey->getId()));
        self::assertEquals('Unrelated value', $manager->find(Translation::class, $foreignTranslationId)->getValue());

        $response = $this->get(['entity' => 'enumoptions', 'id' => self::OPTION_ID], [], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);

        $response = $this->delete(['entity' => 'enumoptions', 'id' => self::OPTION_ID], [], [], false);
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testDeleteDefaultOption(): void
    {
        $option = $this->getEnumOption(self::OPTION_ID);
        $option->setDefault(true);
        $this->getEntityManager()->flush();

        $this->delete(['entity' => 'enumoptions', 'id' => self::OPTION_ID]);

        self::assertNull($this->findEnumOption(self::OPTION_ID));
        self::assertNotNull($this->findEnumOption(self::ENUM_CODE . '.widgets'));
    }

    public function testTryToDeleteImmutableOption(): void
    {
        $optionId = self::OPTION_ID;
        self::assertNotNull($this->findEnumOption($optionId));
        $translationKey = $this->getEnumOptionTranslationKey();

        $config = $this->getFieldConfig('enum', TestEntity1::class, 'testEnumField');
        $originalImmutableCodes = $config->get('immutable_codes', false, []);
        $config->set('immutable_codes', [...$originalImmutableCodes, $optionId]);

        try {
            $response = $this->delete(['entity' => 'enumoptions', 'id' => $optionId], [], [], false);

            $this->assertResponseValidationError(
                [
                    'title' => 'access denied exception',
                    'detail' => 'The enum option is immutable and cannot be deleted.'
                ],
                $response,
                Response::HTTP_FORBIDDEN
            );
            self::assertNotNull($this->findEnumOption($optionId));
            self::assertNotNull($this->getEntityManager()->find(TranslationKey::class, $translationKey->getId()));
        } finally {
            $config->set('immutable_codes', $originalImmutableCodes);
        }
    }

    public function testTryToDeleteOptionInSystemEnum(): void
    {
        $optionId = LoadEnumOptionData::SYSTEM_OPTION_ID;
        $config = $this->getFieldConfig('extend', Dashboard::class, 'dashboard_type');
        self::assertEquals(ExtendScope::OWNER_SYSTEM, $config->get('owner'));

        $response = $this->delete(['entity' => 'enumoptions', 'id' => $optionId], [], [], false);

        $this->assertResponseValidationError(
            [
                'title' => 'access denied exception',
                'detail' => 'Options cannot be deleted from a system enum.'
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
        self::assertNotNull($this->findEnumOption($optionId));
    }

    public function testTryToDeleteUnknownOption(): void
    {
        $response = $this->delete(['entity' => 'enumoptions', 'id' => self::NEW_OPTION_ID], [], [], false);

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
        self::assertNotNull($this->findEnumOption(self::OPTION_ID));
        self::assertNotNull($this->getEnumOptionTranslationKey());
    }

    public function testUpdateOwnTranslation(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), self::LANGUAGE_CODE);
        $data = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'relationships' => [
                    'names' => [
                        'data' => [
                            ['type' => 'translations', 'id' => $translationId]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'translations',
                    'id' => $translationId,
                    'meta' => ['update' => true],
                    'attributes' => [
                        'translatedValue' => 'Updated translation'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            $data
        );

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData($translationKey);
        $this->assertResponseContains($expectedData, $response);
        $translation = $this->getTranslation($translationKey);
        self::assertEquals('Updated translation', $translation->getValue());
        self::assertEquals('2 of 5', $this->getEnumOption(self::OPTION_ID)->getName());
    }

    public function testUpdateMultipleOwnTranslations(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationIdFrFr = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'fr_FR');
        $translationIdEnUs = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_US');
        $data = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 4
                ],
                'relationships' => [
                    'names' => [
                        'data' => [
                            ['type' => 'translations', 'id' => $translationIdFrFr],
                            ['type' => 'translations', 'id' => $translationIdEnUs]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'translations',
                    'id' => $translationIdFrFr,
                    'meta' => ['update' => true],
                    'attributes' => [
                        'translatedValue' => 'Nouvelle valeur'
                    ]
                ],
                [
                    'type' => 'translations',
                    'id' => $translationIdEnUs,
                    'meta' => ['update' => true],
                    'attributes' => [
                        'translatedValue' => 'Updated US name'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            $data
        );

        $expectedData = $data;
        unset($expectedData['included'][0]['meta'], $expectedData['included'][1]['meta']);
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData($translationKey);
        $this->assertResponseContains($expectedData, $response);
        $translationKey = $this->getEnumOptionTranslationKey();
        self::assertEquals('Nouvelle valeur', $this->getTranslation($translationKey)->getValue());
        self::assertEquals('Updated US name', $this->getTranslation($translationKey, 'en_US')->getValue());
        $option = $this->getEnumOption(self::OPTION_ID);
        self::assertEquals(4, $option->getPriority());
        self::assertEquals('2 of 5', $option->getName());
    }

    public function testAddMissingOwnTranslation(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en_CA');
        $data = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'relationships' => [
                    'names' => [
                        'data' => [
                            ['type' => 'translations', 'id' => $translationId]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'translations',
                    'id' => $translationId,
                    'meta' => ['update' => true],
                    'attributes' => [
                        'translatedValue' => 'Canadian option name'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            $data
        );

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData($translationKey);
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals(
            'Canadian option name',
            $this->getTranslation($this->getEnumOptionTranslationKey(), 'en_CA')->getValue()
        );
    }

    public function testTryToCreateDuplicateTranslationWhenUpdatingOption(): void
    {
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => 'new-name-fr']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => 'new-name-fr',
                        'attributes' => [
                            'languageCode' => 'fr_FR',
                            'translatedValue' => 'Should not be saved'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'value constraint',
                'detail' => 'A translation for this language already exists. Use meta.update to update it.',
                'source' => ['pointer' => '/included/0/attributes/languageCode']
            ],
            $response
        );
        self::assertEquals('2 sur 5', $this->getTranslation($this->getEnumOptionTranslationKey())->getValue());
    }

    public function testUpdateEnglishTranslation(): void
    {
        $translationKey = $this->getEnumOptionTranslationKey();
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), 'en');
        $data = [
            'data' => [
                'type' => 'enumoptions',
                'id' => self::OPTION_ID,
                'attributes' => [
                    'priority' => 4
                ],
                'relationships' => [
                    'names' => [
                        'data' => [
                            ['type' => 'translations', 'id' => $translationId]
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'translations',
                    'id' => $translationId,
                    'meta' => ['update' => true],
                    'attributes' => [
                        'translatedValue' => 'Updated English name'
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            $data
        );

        $expectedData = $data;
        unset($expectedData['included'][0]['meta']);
        $expectedData['data']['relationships']['names']['data'] = $this->getAllTranslationsData($translationKey);
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals('Updated English name', $this->getEnumOption(self::OPTION_ID)->getName());
        self::assertEquals('Updated English name', $this->getTranslation($translationKey, 'en')->getValue());
    }

    public function testTryToCreateOptionWithUnrelatedTranslation(): void
    {
        $translationKey = $this->getTranslationKey(self::FOREIGN_TRANSLATION_KEY);
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), self::LANGUAGE_CODE);
        $response = $this->post(
            ['entity' => 'enumoptions'],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::NEW_OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => $translationId]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => $translationId,
                        'meta' => ['update' => true],
                        'attributes' => [
                            'translatedValue' => 'Should not be saved'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
        self::assertEquals('Unrelated value', $this->getTranslation($translationKey)->getValue());
        self::assertNull($this->findEnumOption(self::NEW_OPTION_ID));
    }

    public function testTryToUpdateOptionWithUnrelatedTranslation(): void
    {
        $translationKey = $this->getTranslationKey(self::FOREIGN_TRANSLATION_KEY);
        $translationId = TranslationIdUtil::buildTranslationId($translationKey->getId(), self::LANGUAGE_CODE);
        $response = $this->patch(
            ['entity' => 'enumoptions', 'id' => self::OPTION_ID],
            [
                'data' => [
                    'type' => 'enumoptions',
                    'id' => self::OPTION_ID,
                    'relationships' => [
                        'names' => [
                            'data' => [
                                ['type' => 'translations', 'id' => $translationId]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'translations',
                        'id' => $translationId,
                        'meta' => ['update' => true],
                        'attributes' => [
                            'translatedValue' => 'Should not be saved'
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'entity constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/included/0']
            ],
            $response
        );
        self::assertEquals('Unrelated value', $this->getTranslation($translationKey)->getValue());
        self::assertEquals('2 of 5', $this->getEnumOption(self::OPTION_ID)->getName());
    }
}
