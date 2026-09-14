<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmailTemplateControllerAclTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->loadFixtures([LoadEmailTemplateData::class]);
    }

    public function testCloneWhenViewIsGranted(): void
    {
        $this->client->request(Request::METHOD_GET, $this->getCloneUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testCloneWhenViewIsDenied(): void
    {
        $this->updateRolePermission(
            'ROLE_ADMINISTRATOR',
            EmailTemplate::class,
            AccessLevel::NONE_LEVEL,
            'VIEW'
        );

        $this->client->request(Request::METHOD_GET, $this->getCloneUrl());

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    private function getCloneUrl(): string
    {
        /** @var EmailTemplate $emailTemplate */
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NOT_SYSTEM_TEMPLATE_REFERENCE);

        return $this->getUrl('oro_email_emailtemplate_clone', ['id' => $emailTemplate->getId()]);
    }
}
