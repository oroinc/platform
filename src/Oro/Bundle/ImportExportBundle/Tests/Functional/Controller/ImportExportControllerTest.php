<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Controller;

use Oro\Bundle\ImportExportBundle\Async\Topics;
use Oro\Bundle\ImportExportBundle\Job\JobExecutor;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

        $organization = $this->getTokenAccessor()->getOrganization();
        $organizationId = $organization ? $organization->getId() : null;

        $this->assertMessageSent(Topics::PRE_EXPORT, [
            'jobName' => JobExecutor::JOB_EXPORT_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => null,
            'options' => [],
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $organizationId,
            'securityToken' =>
                'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR'
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

        $organization = $this->getTokenAccessor()->getOrganization();
        $organizationId = $organization ? $organization->getId() : null;

        $this->assertMessageSent(Topics::PRE_EXPORT, [
            'jobName' => JobExecutor::JOB_EXPORT_TEMPLATE_TO_CSV,
            'processorAlias' => 'oro_account',
            'outputFilePrefix' => 'prefix',
            'options' => [
                'first' => 'first value',
                'second' => 'second value',
            ],
            'userId' => $this->getCurrentUser()->getId(),
            'organizationId' => $organizationId,
            'securityToken' =>
                'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR'
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
            Topics::PRE_HTTP_IMPORT,
            [
                'jobName' => JobExecutor::JOB_IMPORT_FROM_CSV,
                'process' => 'import',
                'processorAlias' => 'oro_account',
                'fileName' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
                'securityToken' =>
                    'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR'
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
                    'importValidateJob' => JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
                    'fileName' => 'test_file',
                    'originFileName' => 'test_file_original',
                    'options' => $options,
                ]
            )
        );

        $this->assertJsonResponseSuccess();

        $this->assertMessageSent(
            Topics::PRE_HTTP_IMPORT,
            [
                'jobName' => JobExecutor::JOB_IMPORT_VALIDATION_FROM_CSV,
                'processorAlias' => 'oro_account',
                'process' => 'import_validation',
                'fileName' => 'test_file',
                'originFileName' => 'test_file_original',
                'options' => $options,
                'userId' => $this->getCurrentUser()->getId(),
                'securityToken' =>
                    'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR'

            ]
        );
    }

    public function testImportForm()
    {
        $tmpDirName = $this->getContainer()->getParameter('kernel.root_dir') . '/import_export';
        $fileDir = __DIR__ . '/Import/fixtures';
        $file = $fileDir . '/testLineEndings.csv';

        $finder = new Finder();
        $finder
            ->files()
            ->name('*.csv')
            ->in($tmpDirName);

        $fs = new Filesystem();
        /** @var \SplFileInfo $file */
        foreach ($finder as $importFile) {
            $fs->remove($importFile->getPathname());
        }

        $csvFile = new UploadedFile(
            $fileDir . '/testLineEndings.csv',
            'testLineEndings.csv',
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
        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

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
        $tmpFiles = glob($tmpDirName . DIRECTORY_SEPARATOR . '*.csv');
        $tmpFile = new File($tmpFiles[count($tmpFiles)-1]);
        $this->assertEquals(
            substr_count(file_get_contents($file), "\n"),
            substr_count(file_get_contents($tmpFile->getPathname()), "\r\n")
        );
        unlink($tmpFile->getPathname());
        unlink($file . '_formatted');
    }

    public function testImportValidateExportTemplateFormNoAlias()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate_export_template_form')
        );

        static::assertResponseStatusCodeEquals($this->client->getResponse(), 400);
    }

    public function testImportValidateExportTemplateFormGetRequest()
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

        static::assertResponseStatusCodeEquals($response, 200);
        static::assertContains('Cancel', $response->getContent());
        static::assertContains('Validate Data File', $response->getContent());
        static::assertContains('Import file', $response->getContent());
    }

    public function testImportValidateExportTemplateFormImportValidate()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate_export_template_form'),
            [
                '_widgetContainer' => 'dialog',
                '_wid' => 'test',
                'alias' => 'oro_user',
                'entity' => User::class,
            ]
        );
        
        $form = $crawler->filter('form')->form();

        $fileDir = __DIR__ . '/Import/fixtures';
        $filePath = $fileDir . '/testLineEndings.csv';
        $csvFile = new UploadedFile(
            $filePath,
            'testLineEndings.csv',
            'text/csv'
        );

        $this->client->request(
            'POST',
            $this->getUrl('oro_importexport_import_validate_export_template_form'),
            [
                'alias' => 'oro_user',
                'entity' => User::class,
                'isValidateJob' => true,
                'oro_importexport_import' => [
                    '_token' => $form['oro_importexport_import[_token]']->getValue(),
                ],
            ],
            [
                'oro_importexport_import' =>  [
                    'file' => $csvFile,
                ],
            ]
        );

        $this->assertJsonResponseSuccess();

        $tmpDirPath = static::getContainer()->getParameter('kernel.root_dir') . '/import_export';
        $tmpFiles = glob($tmpDirPath . DIRECTORY_SEPARATOR . '*.csv');
        $tmpFile = new File($tmpFiles[count($tmpFiles)-1]);

        static::assertEquals(
            substr_count(file_get_contents($filePath), "\n"),
            substr_count(file_get_contents($tmpFile->getPathname()), "\r\n")
        );

        static::assertMessageSent(
            Topics::PRE_HTTP_IMPORT,
            [
                'fileName' => $tmpFile->getBasename(),
                'process' => ProcessorRegistry::TYPE_IMPORT_VALIDATION,
                'originFileName' => 'testLineEndings.csv',
                'userId' => $this->getCurrentUser()->getId(),
                'securityToken' =>
                    'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR',
                'jobName' => 'entity_import_validation_from_csv',
                'processorAlias' => 'oro_user.add_or_replace',
                'options' => [],
            ]
        );

        unlink($tmpFile->getPathname());
    }

    public function testImportValidateExportTemplateFormImport()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_importexport_import_validate_export_template_form'),
            [
                '_widgetContainer' => 'dialog',
                '_wid' => 'test',
                'alias' => 'oro_user',
                'entity' => User::class,
            ]
        );

        $form = $crawler->filter('form')->form();

        $fileDir = __DIR__ . '/Import/fixtures';
        $filePath = $fileDir . '/testLineEndings.csv';
        $csvFile = new UploadedFile(
            $filePath,
            'testLineEndings.csv',
            'text/csv'
        );

        $this->client->request(
            'POST',
            $this->getUrl('oro_importexport_import_validate_export_template_form'),
            [
                'alias' => 'oro_user',
                'entity' => User::class,
                'isValidateJob' => false,
                'oro_importexport_import' => [
                    '_token' => $form['oro_importexport_import[_token]']->getValue(),
                ],
            ],
            [
                'oro_importexport_import' =>  [
                    'file' => $csvFile,
                ],
            ]
        );

        $this->assertJsonResponseSuccess();

        $tmpDirPath = static::getContainer()->getParameter('kernel.root_dir') . '/import_export';
        $tmpFiles = glob($tmpDirPath . DIRECTORY_SEPARATOR . '*.csv');
        $tmpFile = new File($tmpFiles[count($tmpFiles)-1]);

        static::assertEquals(
            substr_count(file_get_contents($filePath), "\n"),
            substr_count(file_get_contents($tmpFile->getPathname()), "\r\n")
        );

        static::assertMessageSent(
            Topics::PRE_HTTP_IMPORT,
            [
                'fileName' => $tmpFile->getBasename(),
                'process' => ProcessorRegistry::TYPE_IMPORT,
                'userId' => $this->getCurrentUser()->getId(),
                'originFileName' => 'testLineEndings.csv',
                'securityToken' =>
                    'organizationId=1;userId=1;userClass=Oro\Bundle\UserBundle\Entity\User;roles=ROLE_ADMINISTRATOR',
                'jobName' => 'entity_import_from_csv',
                'processorAlias' => 'oro_user.add_or_replace',
                'options' => [],
            ]
        );

        unlink($tmpFile->getPathname());
    }

    /**
     * @return TokenAccessorInterface
     */
    private function getTokenAccessor()
    {
        return $this->getContainer()->get('oro_security.token_accessor');
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
