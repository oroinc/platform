<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\ImportExport;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ImportExportTest extends WebTestCase
{
    const CLASS_NAME = 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';

    /** @var EntityConfigModel */
    protected $entity;

    /** @var array|FieldConfigModel[] */
    protected $fields;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->entity = $this->getRepository('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->findOneBy(['className' => 'Oro\Bundle\UserBundle\Entity\Role']);

        $this->fields = $this->getEntityFields();
    }

    /**
     * Delete data required because there is commit to job repository in import/export controller action
     * Please use
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->beginTransaction();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->rollback();
     *   $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager()->getConnection()->clear();
     * if you don't use controller
     */
    protected function tearDown()
    {
        // clear DB from separate connection
        $batchJobManager = $this->getContainer()->get('akeneo_batch.job_repository')->getJobManager();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobInstance')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:JobExecution')->execute();
        $batchJobManager->createQuery('DELETE AkeneoBatchBundle:StepExecution')->execute();

        $manager = $this->getManager(self::CLASS_NAME);

        foreach ($this->getEntityFields() as $field) {
            if (!array_key_exists($field->getId(), $this->fields)) {
                $manager->remove($field);
            }
        }

        $manager->flush();

        parent::tearDown();
    }

    public function testImport()
    {
        $this->validateImportFile($this->doExportTemplate());
        $this->doImport(16, 0);

        $this->assertCount(count($this->fields) + 16, $this->getEntityFields());
    }

    public function testChangeFieldTypeError()
    {
        $this->validateImportFile(
            $this->getFilePath('@OroEntityConfigBundle/Tests/Functional/ImportExport/data/string_field.csv')
        );

        $this->doImport(1, 0);
        $this->assertCount(count($this->fields) + 1, $this->getEntityFields());

        $this->assertErrors(
            '@OroEntityConfigBundle/Tests/Functional/ImportExport/data/change_field_type.csv',
            'Error in row #1. Changing type of existing fields is not allowed.'
        );
    }

    public function testImportSystemField()
    {
        $this->validateImportFile(
            $this->getFilePath('@OroEntityConfigBundle/Tests/Functional/ImportExport/data/system_fields.csv')
        );

        $this->doImport(0, 0);
        $this->assertCount(count($this->fields), $this->getEntityFields());
    }

    public function testValidationError()
    {
        $this->assertErrors(
            '@OroEntityConfigBundle/Tests/Functional/ImportExport/data/invalid_field_name.csv',
            ['Data does not contain required properties: type, fieldType or entity_id']
        );

        $this->assertErrors(
            '@OroEntityConfigBundle/Tests/Functional/ImportExport/data/invalid_field_parameters.csv',
            [
                'Error in row #1. attachment.maxsize: This value should be 1 or more.',
                'Error in row #2. Invalid field type.',
                'Error in row #4. enum.enum_options.0: [label]: This value should contain only alphabetic symbols, ' .
                    'underscore, hyphen, spaces and numbers.',
                'Error in row #5. entity.label: This value is too long. It should have 50 characters or less.'
            ]
        );
    }

    /**
     * @return string
     */
    protected function doExportTemplate()
    {
        $this->client->followRedirects(true);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_export_template',
                [
                    'processorAlias' => 'oro_entity_config_entity_field.export_template',
                    'importJob' => 'entity_fields_import_from_csv',
                    'options[entity_id]' => $this->entity->getId()
                ]
            )
        );

        $result = $this->client->getResponse();
        $this->assertResponseStatusCodeEquals($result, 200);
        $this->assertResponseContentTypeEquals($result, 'text/csv');

        /** @var BinaryFileResponse $result */
        return $result->getFile()->getRealPath();
    }

    /**
     * @param string $filePath
     */
    protected function validateImportFile($filePath, $errorsCount = 0)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    '_widgetContainer' => 'dialog',
                    'entity' => self::CLASS_NAME,
                    'importJob' => 'entity_fields_import_from_csv',
                    'options[entity_id]' => $this->entity->getId()
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertFileExists($filePath);

        $form = $crawler->selectButton('Submit')->form();
        $form['oro_importexport_import[file]']->upload($filePath);

        /** TODO Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            sprintf(
                '%s&entity=%s&importJob=entity_fields_import_from_csv&options[entity_id]=%d&_widgetContainer=dialog',
                $form->getFormNode()->getAttribute('action'),
                self::CLASS_NAME,
                $this->entity->getId()
            )
        );

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertEquals($errorsCount, $this->client->getCrawler()->filter('.import-errors')->count());
    }

    /**
     * @param int $added
     * @param int $replaced
     */
    protected function doImport($added, $replaced)
    {
        $this->client->followRedirects(false);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_process',
                [
                    '_format' => 'json',
                    'processorAlias' => 'oro_entity_config_entity_field.add_or_replace',
                    'importJob' => 'entity_fields_import_from_csv',
                    'options[entity_id]' => $this->entity->getId()
                ]
            )
        );

        $data = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEquals(
            [
                'success' => true,
                'message' => 'File was successfully imported.',
                'errorsUrl' => null,
                'importInfo' => sprintf('%s entities were added, %s entities were updated', $added, $replaced)
            ],
            $data
        );
    }

    /**
     * @param string $path
     * @param string|array $errorMessages
     */
    protected function assertErrors($path, $errorMessages)
    {
        $this->validateImportFile($this->getFilePath($path), 1);

        $errors = $this->client->getCrawler()->filter('.import-errors')->html();

        foreach ((array)$errorMessages as $message) {
            $this->assertContains($message, $errors);
        }
    }

    /**
     * @return array|FieldConfigModel[]
     */
    protected function getEntityFields()
    {
        /** @var array|FieldConfigModel[] $fields */
        $fields = $this->getRepository(self::CLASS_NAME)->findBy(['entity' => $this->entity]);
        $result = [];

        foreach ($fields as $field) {
            $result[$field->getId()] = $field;
        }

        return $result;
    }

    /**
     * @param string $className
     * @return ObjectManager
     */
    protected function getManager($className)
    {
        return $this->getContainer()->get('doctrine')->getManagerForClass($className);
    }

    /**
     * @param string $className
     * @return ObjectRepository
     */
    protected function getRepository($className)
    {
        return $this->getManager($className)->getRepository($className);
    }

    /**
     * @param string $file
     * @return string
     */
    protected function getFilePath($file)
    {
        return $this->getContainer()->get('file_locator')->locate($file);
    }
}
