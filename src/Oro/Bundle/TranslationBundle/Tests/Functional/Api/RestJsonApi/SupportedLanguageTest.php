<?php

namespace Oro\Bundle\TranslationBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class SupportedLanguageTest extends RestJsonApiTestCase
{
    use RolePermissionExtension;

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'supportedlanguages']);
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'supportedlanguages', 'id' => 'en']
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(['entity' => 'supportedlanguages', 'id' => 'en']);
        $this->assertResponseContains(
            [
                'data' => ['type' => 'supportedlanguages', 'id' => 'en']
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'supportedlanguages'],
            ['data' => ['type' => 'supportedlanguages']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'supportedlanguages', 'id' => 'en'],
            ['data' => ['type' => 'supportedlanguages', 'id' => 'en']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'supportedlanguages', 'id' => 'en'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'supportedlanguages'],
            ['filter' => ['id' => 'en']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToGetListWhenNoViewAccessToLanguages(): void
    {
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Language::class, AccessLevel::NONE_LEVEL);
        $response = $this->cget(['entity' => 'supportedlanguages'], [], [], false);
        $this->assertResponseValidationError(
            ['title' => 'access denied exception', 'detail' => 'No access to this type of entities.'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }

    public function testTryToGetWhenNoViewAccessToLanguages(): void
    {
        $this->updateRolePermission(User::ROLE_ADMINISTRATOR, Language::class, AccessLevel::NONE_LEVEL);
        $response = $this->get(['entity' => 'supportedlanguages', 'id' => 'en'], [], [], false);
        $this->assertResponseValidationError(
            ['title' => 'access denied exception', 'detail' => 'No access to this type of entities.'],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
