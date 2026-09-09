<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Tests\Functional\OpenApi;

use Oro\Bundle\ApiBundle\Entity\OpenApiSpecification;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class OpenApiSpecificationControllerAclTest extends WebTestCase
{
    use MessageQueueExtension;
    use RolePermissionExtension;

    private OpenApiSpecification $specification;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadUser::class]);
    }

    #[\Override]
    protected function postFixtureLoad(): void
    {
        $this->specification = $this->createOpenApiSpecification();
    }

    public function testCloneWhenViewIsGranted(): void
    {
        $this->client->request(Request::METHOD_GET, $this->getActionUrl('oro_openapi_specification_clone'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testCloneWhenViewIsDenied(): void
    {
        $this->denyPermission('VIEW');

        $this->client->request(Request::METHOD_GET, $this->getActionUrl('oro_openapi_specification_clone'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testRenewWhenEditIsGranted(): void
    {
        $this->ajaxRequest(Request::METHOD_POST, $this->getActionUrl('oro_openapi_specification_renew'));

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertTrue(self::jsonToArray($this->client->getResponse()->getContent())['successful']);
    }

    /**
     * This action changes an existing specification, so it requires EDIT, not CREATE.
     */
    public function testRenewWhenEditIsDenied(): void
    {
        $this->denyPermission('EDIT');

        $this->ajaxRequest(Request::METHOD_POST, $this->getActionUrl('oro_openapi_specification_renew'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testPublishWhenEditIsGranted(): void
    {
        $this->ajaxRequest(Request::METHOD_POST, $this->getActionUrl('oro_openapi_specification_publish'));

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
        self::assertTrue(self::jsonToArray($this->client->getResponse()->getContent())['successful']);
        self::assertTrue($this->getUpdatedSpecification()->isPublished());
    }

    public function testPublishWhenEditIsDenied(): void
    {
        $this->denyPermission('EDIT');

        $this->ajaxRequest(Request::METHOD_POST, $this->getActionUrl('oro_openapi_specification_publish'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertFalse($this->getUpdatedSpecification()->isPublished());
    }

    private function denyPermission(string $permission): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            OpenApiSpecification::class,
            AccessLevel::NONE_LEVEL,
            $permission
        );
    }

    private function getActionUrl(string $route): string
    {
        return $this->getUrl($route, ['id' => $this->specification->getId()]);
    }

    private function getUpdatedSpecification(): OpenApiSpecification
    {
        $em = self::getContainer()->get('doctrine')->getManagerForClass(OpenApiSpecification::class);
        $em->clear();

        return $em->find(OpenApiSpecification::class, $this->specification->getId());
    }

    private function createOpenApiSpecification(): OpenApiSpecification
    {
        /** @var User $user */
        $user = $this->getReference(LoadUser::USER);

        $specification = new OpenApiSpecification();
        $specification->setOwner($user);
        $specification->setOrganization($user->getOrganization());
        $specification->setName('Test OpenAPI Spec');
        $specification->setFormat('json');
        $specification->setView('rest_json_api');
        $specification->setEntities(['organizations']);

        $em = self::getContainer()->get('doctrine')->getManagerForClass(OpenApiSpecification::class);
        $em->persist($specification);
        $em->flush();

        $specification->setStatus(OpenApiSpecification::STATUS_CREATED);
        $specification->setSpecificationCreatedAt(new \DateTime('now', new \DateTimeZone('UTC')));
        $em->flush();

        return $specification;
    }
}
