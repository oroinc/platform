<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Controller;

use Oro\Bundle\ImportExportBundle\Async\Topic\PreExportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\PreImportTopic;
use Oro\Bundle\ImportExportBundle\Async\Topic\SaveImportExportResultTopic;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfiguration;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationInterface;
use Oro\Bundle\ImportExportBundle\Configuration\ImportExportConfigurationProviderInterface;
use Oro\Bundle\ImportExportBundle\Controller\ImportExportController;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Oro\Bundle\ImportExportBundle\File\FileManager;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Tests\Functional\DataFixtures\LoadImportExportResultData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ImportExportControllerTest extends WebTestCase
{
    use MessageQueueExtension;
    use TempDirExtension;

    /**
     * @var array
     */
    private $existingFiles = [];

    protected function setUp(): void
    {
        $this->initClient([], self::generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([
            LoadImportExportResultData::class
        ]);

        $this->existingFiles = $this->getImportExportFileManager()->getFilesByFilePattern('*.csv');
    }

    protected function tearDown(): void
    {
        $fileManager = $this->getImportExportFileManager();
        $tempFiles = $fileManager->getFilesByFilePattern('*.csv');
        $diffFiles = array_diff($tempFiles, $this->existingFiles);
        foreach ($diffFiles as $file) {
            $fileManager->deleteFile($file);
        }
    }

    public function testShouldSendExportMessageOnInstantExportActionWithDefaultParameters(): void
    {
        $this->ajaxRequest(
            'POST',
            $this->getUrl('oro_importexport_export_instant', ['processorAlias' => 'oro_account'])
        );

        $this->assertJsonResponseSuccessOnExport();

        $organization = $this->getTokenAccessor()->getOrganization();

        self::assertMessageSent(PreExportTopic::getName(), [
            'jobName' => JobExecutor::JOB_EXPORT_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => null,
            'options' => [],
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $organization?->getId(),
        ]);
    }

    public function testShouldSendExportMessageOnInstantExportActionWithPassedParameters(): void
    {
        $this->ajaxRequest(
            'POST',
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

        $this->assertJsonResponseSuccessOnExport();

        $organization = $this->getTokenAccessor()->getOrganization();

        self::assertMessageSent(PreExportTopic::getName(), [
            'jobName' => JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => 'prefix',
            'options' => [
                'first' => 'first value',
                'second' => 'second value',
            ],
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $organization?->getId(),
        ]);
    }

    public function testDownloadFileReturns404IfFileDoesntExist(): void
    {
        $undefinedJobId = 999;
        $this->client->followRedirects(true);

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', [
                'jobId' => $undefinedJobId
            ])
        );

        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 404);
    }

    public function testImportProcessAction(): void
    {
        $options = [
            'first' => 'first value',
            'second' => 'second value',
        ];
        $this->ajaxRequest(
            'POST',
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

        self::assertMessageSent(
            PreImportTopic::getName(),
            [
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'process' => 'import',
                'processorAlias' => 'oro_account',
                'fileName' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
            ]
        );
    }

    public function testImportProcessActionWithCustomProcessorTopicName()
    {
        $importProcessorTopicName = SaveImportExportResultTopic::getName();

        $options = [
            'first' => 'first value',
            'second' => 'second value',
        ];
        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    'processorAlias' => 'oro_account',
                    'importJob' => JobExecutor::JOB_IMPORT_FROM_CSV,
                    'fileName' => 'test_file',
                    'originFileName' => 'test_file_original',
                    'options' => $options,
                    'importProcessorTopicName' => $importProcessorTopicName,
                ]
            )
        );

        $this->assertJsonResponseSuccess();

        self::assertMessageSent(
            $importProcessorTopicName,
            [
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'process' => 'import',
                'processorAlias' => 'oro_account',
                'fileName' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
            ]
        );
    }

    public function testImportValidateAction(): void
    {
        $options = [
            'first' => 'first value',
            'second' => 'second value',
        ];
        $this->ajaxRequest(
            'POST',
            $this->getUrl(
                'oro_importexport_import_validate',
                [
                    'processorAlias' => 'oro_account',
                    'importValidateJob' => JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
                    'fileName' => 'test_file',
                    'originFileName' => 'test_file_original',
                    'options' => $options,
                ]
            )
        );

        $this->assertJsonResponseSuccess();

        self::assertMessageSent(
            PreImportTopic::getName(),
            [
                'jobName' => JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
                'processorAlias' => 'oro_account',
                'process' => 'import_validation',
                'fileName' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
            ]
        );
    }

    public function testImportForm(): void
    {
        $fileName = 'oro_testLineEndings.csv';

        $file = $this->copyToTempDir('import_export', __DIR__ . '/Import/fixtures')
            . DIRECTORY_SEPARATOR
            . $fileName;
        $csvFile = new UploadedFile(
            $file,
            $fileName,
            'text/csv'
        );
        $this->assertEquals(
            substr_count(file_get_contents($file), "\r\n"),
            substr_count(file_get_contents($csvFile->getPathname()), "\r\n")
        );
        $this->assertEquals(
            substr_count(file_get_contents($file), "\n"),
            substr_count(file_get_contents($csvFile->getPathname()), "\n")
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test',
                    'entity' => User::class,
                ]
            )
        );
        self::assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $uploadFileNode = $crawler->selectButton('Submit');
        $uploadFileForm = $uploadFileNode->form();
        $values = [
            'oro_importexport_import' => [
                '_token' => $uploadFileForm['oro_importexport_import[_token]']->getValue(),
                'processorAlias' => 'oro_user.add_or_replace'
            ],
        ];
        $files = [
            'oro_importexport_import' => [
                'file' => $csvFile
            ]
        ];
        $this->client->request(
            $uploadFileForm->getMethod(),
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    '_widgetContainer' => 'dialog',
                    '_wid' => 'test',
                    'entity' => User::class,
                ]
            ),
            $values,
            $files
        );
        $this->assertJsonResponseSuccess();
        $message = self::getSentMessage(PreImportTopic::getName());

        $importedFileContent = $this->getImportExportFileManager()->getContent($message['fileName']);
        self::assertEquals(
            substr_count(file_get_contents($file), "\n"),
            substr_count($importedFileContent, "\r\n")
        );
    }

    public function testImportValidateExportTemplateFormNoAlias(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate_export_template_form')
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), 400);
    }

    public function testImportValidateExportTemplateFormGetRequest(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate_export_template_form'),
            [
                'alias' => 'alias',
                'entity' => 'entity',
            ]
        );

        $response = $this->client->getResponse();

        self::assertResponseStatusCodeEquals($response, 200);
        self::assertStringContainsString('Cancel', $response->getContent());
        self::assertStringContainsString('Validate', $response->getContent());
        self::assertStringContainsString('Import file', $response->getContent());
    }

    public function testImportValidateExportTemplateFormAction(): void
    {
        $registry = self::getContainer()->get('oro_importexport.configuration.registry');
        $registry->addConfiguration(
            new class() implements ImportExportConfigurationProviderInterface {
                /**
                 * {@inheritdoc}
                 */
                public function get(): ImportExportConfigurationInterface
                {
                    return new ImportExportConfiguration([
                        ImportExportConfiguration::FIELD_ENTITY_CLASS => \stdClass::class,
                    ]);
                }
            },
            'oro_test'
        );

        $controller = self::getContainer()->get(ImportExportController::class);

        $this->assertEquals(
            [
                'options' => [],
                'alias' => 'oro_test',
                'configsWithForm' => [],
                'chosenEntityName' => \stdClass::class,
                'entityVisibility' => []
            ],
            $controller->importValidateExportTemplateFormAction(
                new Request(['alias' => 'oro_test', 'entity' => \stdClass::class])
            )
        );

        $registry->addConfiguration(
            new class() implements ImportExportConfigurationProviderInterface {
                /**
                 * {@inheritdoc}
                 */
                public function get(): ImportExportConfigurationInterface
                {
                    return new ImportExportConfiguration([
                        ImportExportConfiguration::FIELD_ENTITY_CLASS => \stdClass::class,
                        ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_test',
                    ]);
                }
            },
            'oro_test'
        );

        $formFactory = self::getContainer()->get('form.factory');

        $this->assertEquals(
            [
                'options' => [],
                'alias' => 'oro_test',
                'configsWithForm' => [
                    [
                        'form' => $formFactory->create(ImportType::class, null, ['entityName' => \stdClass::class]),
                        'configuration' => new ImportExportConfiguration([
                            ImportExportConfiguration::FIELD_ENTITY_CLASS => \stdClass::class,
                            ImportExportConfiguration::FIELD_IMPORT_PROCESSOR_ALIAS => 'oro_test',
                        ])
                    ]
                ],
                'chosenEntityName' => \stdClass::class,
                'entityVisibility' => [
                    'stdClass' => true
                ]
            ],
            $controller->importValidateExportTemplateFormAction(
                new Request(['alias' => 'oro_test', 'entity' => \stdClass::class])
            )
        );
    }

    public function testDownloadExportResultActionExpiredResult(): void
    {
        /** @var ImportExportResult $expiredImportExportResult */
        $expiredImportExportResult = $this->getReference('expiredImportExportResult');

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_export_download', [
                'jobId' => $expiredImportExportResult->getJobId()
            ])
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 410);
    }

    public function testImportExportJobErrorLogActionExpiredResult(): void
    {
        /** @var ImportExportResult $expiredImportExportResult */
        $expiredImportExportResult = $this->getReference('expiredImportExportResult');

        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_job_error_log', [
                'jobId' => $expiredImportExportResult->getJobId()
            ])
        );

        self::assertJsonResponseStatusCodeEquals($this->client->getResponse(), 410);
    }

    private function getImportExportFileManager(): FileManager
    {
        return self::getContainer()->get('oro_importexport.file.file_manager');
    }

    /**
     * @return TokenAccessorInterface
     */
    private function getTokenAccessor()
    {
        return self::getContainer()->get('oro_security.token_accessor');
    }

    /**
     * @return mixed
     */
    private function getCurrentUser()
    {
        return self::getContainer()->get('security.token_storage')->getToken()->getUser();
    }

    private function assertJsonResponseSuccessOnExport()
    {
        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertTrue($result['success']);
    }

    private function assertJsonResponseSuccess()
    {
        $result = self::getJsonResponseContent($this->client->getResponse(), 200);

        self::assertNotEmpty($result);
        self::assertCount(2, $result);
        self::assertTrue($result['success']);
        self::assertContainsEquals('message', $result);
    }
}
