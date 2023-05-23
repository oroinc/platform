<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\TranslationBundle\Tests\Functional\Api\DataFixtures\LoadTranslations;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TranslationCreateTest extends RestJsonApiTestCase
{
    use ResolveTranslationIdTrait;
    use RolePermissionExtension;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadTranslations::class]);
    }

    public function testUpdateExistingTranslation(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>'),
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
        self::assertEquals(
            $this->getUrl(
                'oro_rest_api_item',
                ['entity' => 'translations', 'id' => $this->getResourceId($response)],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $response->headers->get('Location')
        );
    }

    public function testUpdateNotExistingTranslation(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_US',
                        'translatedValue' => 'updated trans1 (en_US)'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_US->code>'),
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
        self::assertEquals(
            $this->getUrl(
                'oro_rest_api_item',
                ['entity' => 'translations', 'id' => $this->getResourceId($response)],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $response->headers->get('Location')
        );
    }

    public function testUpdateExistingTranslationWhenDeleteTranslationRequest(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'translatedValue' => null
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>'),
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
        self::assertEquals(
            $this->getUrl(
                'oro_rest_api_item',
                ['entity' => 'translations', 'id' => $this->getResourceId($response)],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            $response->headers->get('Location')
        );
    }

    public function testUpdateNotExistingTranslationWhenDeleteTranslationRequest(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_US',
                        'translatedValue' => null
                    ]
                ]
            ]
        );
        $this->assertResponseContains(['data' => null], $response);
        self::assertFalse($response->headers->has('Location'));
    }

    public function testUpdateShouldIgnoreReadOnlyFields(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
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
                    'id'         => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>'),
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

    public function testTryToUpdateWithoutLanguageCode(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/languageCode']
            ],
            $response
        );
    }

    public function testTryToUpdateWithoutDomain(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/domain']
            ],
            $response
        );
    }

    public function testTryToUpdateWithoutKey(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'languageCode'    => 'en_CA',
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/key']
            ],
            $response
        );
    }

    public function testTryToUpdateWithoutDomainKeyAndLanguageCode(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'translatedValue' => 'updated trans1 (en_CA)'
                    ]
                ]
            ],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/key']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/domain']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/languageCode']
                ]
            ],
            $response
        );
    }

    public function testUpdateExistingTranslationWithoutTranslatedValue(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'       => 'test_domain',
                        'key'          => 'test.trans1',
                        'languageCode' => 'en_CA'
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'translations',
                    'id'         => $this->resolveTranslationId('<@tk-test_trans1-test_domain->id>-<@en_CA->code>'),
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

    public function testTryToUpdateWithEmptyAttributes(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            ['data' => ['type' => 'translations']],
            [],
            false
        );
        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/key']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/domain']
                ],
                [
                    'title'  => 'not blank constraint',
                    'detail' => 'This value should not be blank.',
                    'source' => ['pointer' => '/data/attributes/languageCode']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateForUnknownDomain(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'unknown_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
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
                'detail' => 'An entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdateForUnknownKey(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1.unknown',
                        'languageCode'    => 'en_CA',
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
                'detail' => 'An entity does not exist.'
            ],
            $response,
            Response::HTTP_NOT_FOUND
        );
    }

    public function testTryToUpdateForUnknownLanguageCode(): void
    {
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'UNKNOWN',
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
                'detail' => 'An entity does not exist.'
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
        $response = $this->post(
            ['entity' => 'translations'],
            [
                'data' => [
                    'type'       => 'translations',
                    'attributes' => [
                        'domain'          => 'test_domain',
                        'key'             => 'test.trans1',
                        'languageCode'    => 'en_CA',
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
