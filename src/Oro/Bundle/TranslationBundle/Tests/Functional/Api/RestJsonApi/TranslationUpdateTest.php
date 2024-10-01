<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures\LoadTranslations;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class TranslationUpdateTest extends RestJsonApiTestCase
{
    use ResolveTranslationIdTrait;
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testUpdateExistingTranslation(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'hasTranslation'  => true,
                        'value'           => 'updated trans1 (en_CA)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateNotExistingTranslation(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_US)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
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
            ],
            $response
        );
    }

    public function testUpdateExistingTranslationWhenDeleteTranslationRequest(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => null
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'hasTranslation'  => false,
                        'value'           => 'test trans1 (en)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateNotExistingTranslationWhenDeleteTranslationRequest(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => null
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_US',
                        'hasTranslation'  => false,
                        'value'           => 'test trans1 (en)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => null
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateShouldIgnoreReadOnlyFields(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain_another',
                        'key'             => 'test.trans1.another',
                        'languageCode'    => 'en_US',
                        'hasTranslation'  => false,
                        'value'           => 'test trans1 (en_CA) another',
                        'englishValue'    => 'test trans1 (en) another',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'hasTranslation'  => true,
                        'value'           => 'updated trans1 (en_CA)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateExistingTranslationWithoutTranslatedValue(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type' => 'translations',
                    'id'   => $translationId
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'hasTranslation'  => true,
                        'value'           => 'test trans1 (en_CA)',
                        'englishValue'    => 'test trans1 (en)',
                        'translatedValue' => 'test trans1 (en_CA)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForUnknownTranslationKeyId(): void
    {
        $translationId = $this->resolveTranslationId('999999-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdateForUnknownLanguageCode(): void
    {
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-UNKNOWN');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not found http exception',
                'detail' => 'An entity with the requested identifier does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdateTranslationWhenNoTranslateAccessToLanguages(): void
    {
        $this->updateRolePermissions(
            User::ROLE_ADMINISTRATOR,
            Language::class,
            ['VIEW' => AccessLevel::GLOBAL_LEVEL, 'TRANSLATE' => AccessLevel::NONE_LEVEL]
        );
        $translationId = $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>');
        $response = $this->patch(
            ['entity' => 'translations', 'id' => $translationId],
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $translationId,
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            ['title' => 'access denied exception', 'detail' => 'No access to this type of entities.'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
