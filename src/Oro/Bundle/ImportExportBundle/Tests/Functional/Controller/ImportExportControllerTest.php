<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Controller;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ImportExportControllerTest extends WebTestCase
{
    use MessageQueueExtension;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testShouldSendExportMessageOnInstantExportActionWithDefaultParameters()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_instant', ['processorAlias' => 'oro_account'])
        );

        $this->assertJsonResponseSuccess();

        $organization = $this->getSecurityFacade()->getOrganization();
        $organizationId = $organization ? $organization->getId() : null;

        $userId = $this->getCurrentUser()->getId();
        $token = sprintf(
            'organizationId=%s;userId=%s;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR',
            $organizationId,
            $userId
        );
        $this->assertMessageSent(Topics::EXPORT, [
            'jobName' => JobExecutor::JOB_EXPORT_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => null,
            'options' => [],
            'userId' => $userId,
            'organizationId' => $organizationId,
            'securityToken' => $token
        ]);
    }

    public function testShouldSendExportMessageOnInstantExportActionWithPassedParameters()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_instant', [
                'processorAlias' => 'oro_account',
                'exportJob' => JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
                'filePrefix' => 'prefix',
                'options' => [
                    'first' => 'first value',
                    'second' => 'second value',
                ]
            ])
        );

        $this->assertJsonResponseSuccess();

        $organization = $this->getSecurityFacade()->getOrganization();
        $organizationId = $organization ? $organization->getId() : null;
        $userId = $this->getCurrentUser()->getId();
        $token = sprintf(
            'organizationId=%s;userId=%s;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR',
            $organizationId,
            $userId
        );
        $this->assertMessageSent(Topics::EXPORT, [
            'jobName' => JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => 'prefix',
            'options' => [
                'first' => 'first value',
                'second' => 'second value',
            ],
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $organizationId,
            'securityToken' => $token
        ]);
    }

    public function testImportProcessAction()
    {
        $options = [
            'first' => 'first value',
            'second' => 'second value',
        ];
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => 'oro_account',
                    'importJob' => JobExecutor::JOB_IMPORT_FROM_CSV,
                    'fileName' => 'test_file',
                    'originFileName' => 'test_file_original',
                    'options' => $options,
                ]
            )
        );

        $this->assertJsonResponseSuccess();

        $this->assertMessageSent(
            Topics::IMPORT_HTTP_PREPARING,
            [
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'processorAlias' => 'oro_account',
                'filePath' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
            ]
        );
    }

    public function testImportValidateAction()
    {
        $options = [
            'first' => 'first value',
            'second' => 'second value',
        ];
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_validate',
                [
                    'processorAlias' => 'oro_account',
                    'importValidateJob' => JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
                    'fileName' => 'test_file',
                    'originFileName' => 'test_file_original',
                    'options' => $options,
                ]
            )
        );

        $this->assertJsonResponseSuccess();

        $this->assertMessageSent(
            Topics::IMPORT_HTTP_VALIDATION_PREPARING,
            [
                'jobName' => JobExecutor::JOB_VALIDATE_IMPORT_FROM_CSV,
                'processorAlias' => 'oro_account',
                'filePath' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
            ]
        );
    }

    /**
     * @return object
     */
    private function getSecurityFacade()
    {
        return $this->getContainer()->get('oro_security.security_facade');
    }

    /**
     * @return mixed
     */
    private function getCurrentUser()
    {
        return $this->getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    private function assertJsonResponseSuccess()
    {
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result['success']);
    }
}
