<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateWithTestActivityData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
final class AjaxEmailControllerTest extends WebTestCase
{
    use RolePermissionExtension;

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadEmailTemplateWithTestActivityData::class]);
    }

    public function testCompileEmailActionWhenNoEmailTemplatePermission(): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY_TEMPLATE);
        /** @var TestActivity $testActivity */
        $testActivity = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY);

        // Only the email template permission is revoked. The permission the route itself requires
        // (oro_email_email_create) is left intact, so the refusal can only come from the new check.
        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::NONE_LEVEL);

        $this->sendCompileEmailRequest([
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => TestActivity::class,
                'entityId' => $testActivity->getId(),
            ],
        ]);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testCompileEmailActionWhenEmailTemplateBelongsToAnotherUser(): void
    {
        $this->loadFixtures([LoadEmailTemplateData::class]);

        /** @var User $user */
        $user = $this->getReference(LoadEmailTemplateData::OWNER_USER_REFERENCE);
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_TEMPLATE_REFERENCE);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::BASIC_LEVEL);

        $this->sendCompileEmailRequest([
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => User::class,
                'entityId' => $user->getId(),
            ],
        ]);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testCompileEmailActionWhenNoTargetEntityPermission(): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY_TEMPLATE);
        /** @var TestActivity $testActivity */
        $testActivity = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', TestActivity::class, AccessLevel::NONE_LEVEL);

        $this->sendCompileEmailRequest([
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => TestActivity::class,
                'entityId' => $testActivity->getId(),
            ],
        ]);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testCompileEmailActionWhenTargetEntityDoesNotExist(): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY_TEMPLATE);

        $this->sendCompileEmailRequest([
            'oro_email_email' => [
                'from' => 'test@example.com',
                'to' => ['recipient@example.com'],
                'template' => $emailTemplate->getId(),
                'entityClass' => TestActivity::class,
                'entityId' => self::BIGINT,
            ],
        ]);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    private function sendCompileEmailRequest(array $parameters): void
    {
        $csrfToken = $this->getCsrfToken(CsrfRequestManager::CSRF_TOKEN_ID);
        $this->client->getCookieJar()->set(new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, $csrfToken->getValue()));

        $this->client->request(
            'POST',
            $this->getUrl('oro_email_ajax_email_compile'),
            $parameters,
            [],
            ['HTTP_X-CSRF-Header' => $csrfToken->getValue()]
        );
    }
}
