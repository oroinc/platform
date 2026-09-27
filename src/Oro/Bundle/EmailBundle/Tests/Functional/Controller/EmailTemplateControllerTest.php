<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
final class EmailTemplateControllerTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadEmailTemplateData::class]);
    }

    public function testPreviewAction(): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_TEMPLATE_REFERENCE);

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_emailtemplate_preview', ['id' => $emailTemplate->getId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testPreviewActionForNewEmailTemplate(): void
    {
        $this->client->request('GET', $this->getUrl('oro_email_emailtemplate_preview', ['id' => 0]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testPreviewActionWhenEmailTemplateDoesNotExist(): void
    {
        $this->client->request('GET', $this->getUrl('oro_email_emailtemplate_preview', ['id' => self::BIGINT]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testPreviewActionWhenNoEmailTemplatePermission(): void
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_TEMPLATE_REFERENCE);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::NONE_LEVEL);

        $this->client->request(
            'GET',
            $this->getUrl('oro_email_emailtemplate_preview', ['id' => $emailTemplate->getId()])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }
}
