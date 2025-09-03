<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\EmailTemplateAttachment;
use Oro\Bundle\EmailBundle\Form\Model\EmailAttachment;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadAjaxEmailControllerData;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class AjaxEmailControllerTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadAjaxEmailControllerData::class]);
    }

    public function testCompileEmailActionWithValidEmailTemplate(): void
    {
        $user = $this->getReference('simple_user');
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference('valid_email_template');

        $parameters = [
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => User::class,
                'entityId' => $user->getId(),
            ],
        ];

        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_ajax_email_compile'),
            $parameters,
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('subject', $responseData);
        self::assertArrayHasKey('body', $responseData);
        self::assertArrayHasKey('type', $responseData);
        self::assertArrayHasKey('attachments', $responseData);

        self::assertStringContainsString('Valid Subject ' . $user->getUsername(), $responseData['subject']);
        self::assertStringContainsString('Valid template content ' . $user->getEmail(), $responseData['body']);
        self::assertNull($responseData['type']);
        self::assertIsArray($responseData['attachments']);
    }

    public function testCompileEmailActionWithInvalidTemplate(): void
    {
        $user = $this->getReference('simple_user');
        $emailTemplate = $this->getReference('invalid_email_template');

        $parameters = [
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => User::class,
                'entityId' => $user->getId(),
            ],
        ];

        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_ajax_email_compile'),
            $parameters,
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, Response::HTTP_UNPROCESSABLE_ENTITY);

        self::assertArrayHasKey('reason', $responseData);
        self::assertStringContainsString("this email template can't be used", strtolower($responseData['reason']));
    }

    public function testCompileEmailActionWithAttachments(): void
    {
        /** @var User $user */
        $user = $this->getReference('simple_user');
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference('email_template_with_attachments');
        /** @var EmailTemplateAttachment $emailTemplateAttachment */
        $emailTemplateAttachment = $this->getReference('email_template_attachment');

        $parameters = [
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => User::class,
                'entityId' => $user->getId(),
            ],
        ];

        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_ajax_email_compile'),
            $parameters,
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, Response::HTTP_OK);

        self::assertArrayHasKey('attachments', $responseData);
        self::assertIsArray($responseData['attachments']);
        self::assertCount(1, $responseData['attachments']);
        self::assertEquals([
            [
                'id' => $emailTemplateAttachment->getId() . ':0',
                'type' => EmailAttachment::TYPE_EMAIL_TEMPLATE_ATTACHMENT,
                'fileName' => $emailTemplateAttachment->getFile()->getOriginalFilename(),
                'icon' => 'fa-file-pdf-o',
                'errors' => [],
            ],
        ], $responseData['attachments']);
    }

    public function testCompileEmailActionWithGetMethod(): void
    {
        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_ajax_email_compile'),
            [],
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
    }

    public function testCompileEmailActionWithoutEmailTemplate(): void
    {
        $user = $this->getReference('simple_user');

        $parameters = [
            'oro_email_email' => [
                'entityClass' => User::class,
                'entityId' => $user->getId(),
                'from' => 'test@example.com',
                'to' => 'recipient@example.com',
            ],
        ];

        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_ajax_email_compile'),
            $parameters,
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_OK);

        $responseData = json_decode($response->getContent(), true);
        self::assertIsArray($responseData);
        self::assertArrayHasKey('subject', $responseData);
        self::assertArrayHasKey('body', $responseData);
        self::assertArrayHasKey('type', $responseData);
        self::assertArrayHasKey('attachments', $responseData);

        self::assertNull($responseData['subject']);
        self::assertNull($responseData['body']);
        self::assertNull($responseData['type']);
        self::assertIsArray($responseData['attachments']);
    }
}
