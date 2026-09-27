<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateData;
use Oro\Bundle\EmailBundle\Tests\Functional\DataFixtures\LoadEmailTemplateWithTestActivityData;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Test\Functional\RolePermissionExtension;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmailTemplateControllerTest extends WebTestCase
{
    use RolePermissionExtension;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], self::generateApiAuthHeader());

        $this->loadFixtures([LoadEmailTemplateData::class]);
    }

    public function testGetWithoutParams()
    {
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates')
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testGet()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', ['entityName' => $entityName])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
    }

    public function testGetNonSystemNoEntity()
    {
        /** @var EmailTemplate $template */
        $template = $this->getReference('emailTemplate3');
        $entityName = str_replace('\\', '_', $template->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 0,
                'includeSystemTemplates' => 0
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                [
                    'id'          => $template->getId(),
                    'name'        => $template->getName(),
                    'is_system'   => $template->getIsSystem(),
                    'is_editable' => $template->getIsEditable(),
                    'content'     => $template->getContent(),
                    'entity_name' => $template->getEntityName(),
                    'type'        => $template->getType()
                ]
            ],
            $result
        );
    }

    public function testGetNonSystemEntity()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 1,
                'includeSystemTemplates' => 0
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(3, $result);
    }

    public function testGetSystemNonEntity()
    {
        $entityName = str_replace('\\', '_', $this->getReference('emailTemplate3')->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 0,
                'includeSystemTemplates' => 1
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(2, $result);
    }

    public function testGetEntitySystem()
    {
        $reference = $this->getReference('emailTemplate3');
        $entityName = str_replace('\\', '_', $reference->getEntityName());
        $this->client->jsonRequest(
            'GET',
            $this->getUrl('oro_api_get_emailtemplates', [
                'entityName' => $entityName,
                'includeNonEntity' => 1,
                'includeSystemTemplates' => 1
            ])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $em = self::getContainer()->get('doctrine.orm.entity_manager');
        $expectedTemplates = $em
            ->getRepository(EmailTemplate::class)
            ->findBy(['entityName' => [$reference->getEntityName(), null], 'visible' => true]);

        self::assertCount(count($expectedTemplates), $result);
    }

    /**
     * Check that server return rendered template with defined data structure
     */
    public function testGetCompiledEmailTemplate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $emailTemplate = $em->getRepository(EmailTemplate::class)->findOneBy(['name' => 'test_template']);

        $user = $em->getRepository(User::class)->findOneBy(['username' => 'simple_user']);
        $this->assertNotNull($user);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => $user->getId()]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('type', $data);
    }

    /**
     * Check that server return rendered system template with defined data structure
     * Template without related entity
     */
    public function testGetCompiledSystemEmailTemplate()
    {
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $emailTemplate = $this->getReference('emailTemplate1');
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'simple_user']);
        $this->assertNotNull($user);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => '']
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('body', $data);
        $this->assertArrayHasKey('subject', $data);
        $this->assertArrayHasKey('type', $data);
    }

    /**
     * Check that server return not found message
     */
    public function testGetCompiledEmailTemplateNoEntityFound()
    {
        $emailTemplate = $this->getReference('emailTemplate2');
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 0]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 404);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('message', $data);
    }

    /**
     * Check that server returns 422 HTTP error when failed to compile email template
     */
    public function testGetCompiledEmailCompileFailed()
    {
        $emailTemplate = $this->getReference(LoadEmailTemplateData::SYSTEM_FAIL_TO_COMPILE);
        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => 1]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 422);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('reason', $data);
    }

    public function testGetCompiledEmailTemplateWhenNoEmailTemplatePermission(): void
    {
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::NONE_LEVEL);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => '']
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testGetCompiledEmailTemplateWhenBasicPermissionAndEmailTemplateOwnedByAnotherUser(): void
    {
        $emailTemplate = $this->getReference(LoadEmailTemplateData::NO_ENTITY_NAME_TEMPLATE_REFERENCE);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::BASIC_LEVEL);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => '']
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testGetVariables(): void
    {
        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_emailtemplate_variables'));

        $data = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);

        self::assertArrayHasKey('system', $data);
        self::assertArrayHasKey('entity', $data);
    }

    public function testGetVariablesWhenNoEmailTemplatePermission(): void
    {
        $this->updateRolePermission('ROLE_ADMINISTRATOR', EmailTemplate::class, AccessLevel::NONE_LEVEL);

        $this->client->jsonRequest('GET', $this->getUrl('oro_api_get_emailtemplate_variables'));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    public function testGetCompiledEmailTemplateWhenNoTargetEntityPermission(): void
    {
        $this->loadFixtures([LoadEmailTemplateWithTestActivityData::class]);

        $emailTemplate = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY_TEMPLATE);
        $testActivity = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY);

        $this->updateRolePermission('ROLE_ADMINISTRATOR', TestActivity::class, AccessLevel::NONE_LEVEL);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => $testActivity->getId()]
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
    }

    /**
     * Guards that the target record access check does not turn a missing record into "access denied".
     */
    public function testGetCompiledEmailTemplateWhenTargetEntityDoesNotExist(): void
    {
        $this->loadFixtures([LoadEmailTemplateWithTestActivityData::class]);

        $emailTemplate = $this->getReference(LoadEmailTemplateWithTestActivityData::TEST_ACTIVITY_TEMPLATE);

        $this->client->jsonRequest(
            'GET',
            $this->getUrl(
                'oro_api_get_emailtemplate_compiled',
                ['id' => $emailTemplate->getId(), 'entityId' => self::BIGINT]
            )
        );

        $data = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_NOT_FOUND);

        self::assertArrayHasKey('message', $data);
    }
}
