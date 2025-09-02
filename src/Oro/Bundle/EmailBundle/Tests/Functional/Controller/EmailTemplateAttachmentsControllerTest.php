<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

final class EmailTemplateAttachmentsControllerTest extends WebTestCase
{
    public function testGetAttachmentChoicesActionRequiresAuthentication(): void
    {
        $this->initClient();

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => User::class,
            ])
        );

        $response = $this->client->getResponse();
        self::assertEquals(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testGetAttachmentChoicesActionWithValidEntityClass(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => User::class,
            ])
        );

        $response = $this->client->getResponse();
        $data = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('successful', $data);
        self::assertTrue($data['successful']);
        self::assertArrayHasKey('choices', $data);
        self::assertIsArray($data['choices']);
    }

    public function testGetAttachmentChoicesActionWithInvalidEntityClass(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => 'InvalidEntityClass',
            ])
        );

        $response = $this->client->getResponse();
        $data = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('successful', $data);
        self::assertTrue($data['successful']);
        self::assertArrayHasKey('choices', $data);
        self::assertIsArray($data['choices']);
        self::assertEmpty($data['choices']);
    }

    public function testGetAttachmentChoicesActionWithNonExistentEntityClass(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => 'App\\Entity\\NonExistentEntity',
            ])
        );

        $response = $this->client->getResponse();
        $data = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('successful', $data);
        self::assertTrue($data['successful']);
        self::assertArrayHasKey('choices', $data);
        self::assertIsArray($data['choices']);
        self::assertEmpty($data['choices']);
    }

    public function testGetAttachmentChoicesActionReturnsCorrectStructure(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => User::class,
            ])
        );

        $response = $this->client->getResponse();
        $data = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('successful', $data);
        self::assertArrayHasKey('choices', $data);

        foreach ($data['choices'] as $choice) {
            self::assertIsArray($choice);
            self::assertArrayHasKey('value', $choice);
            self::assertArrayHasKey('label', $choice);
            self::assertIsString($choice['value']);
            self::assertIsString($choice['label']);
        }
    }

    public function testGetAttachmentChoicesActionWithGetMethodReturnsForbidden(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_emailtemplate_ajax_get_attachment_choices', [
                'entityName' => User::class,
            ])
        );

        $response = $this->client->getResponse();
        self::assertEquals(Response::HTTP_METHOD_NOT_ALLOWED, $response->getStatusCode());
    }
}
