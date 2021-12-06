<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\ImportExport;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class ImportExportTest extends WebTestCase
{
    /** @var EntityConfigModel */
    private $entity;

    /** @var FieldConfigModel[] */
    private $fields;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->entity = $this->getRepository(EntityConfigModel::class)
            ->findOneBy(['className' => Role::class]);

        $this->fields = $this->getEntityFields();
    }

    public function testImport()
    {
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->validateImportFile($this->doExportTemplate());
        $this->doImport(16, 0);

        $this->assertCount(count($this->fields) + 16, $this->getEntityFields());
    }

    public function testChangeFieldTypeError()
    {
        $this->validateImportFile(
            $this->getFilePath('@OroEntityConfigBundle/Tests/Functional/ImportExport/data/string_field.csv')
        );

        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
        $this->doImport(1, 0);
        $this->assertCount(count($this->fields) + 1, $this->getEntityFields());

        $this->assertErrors(
            '@OroEntityConfigBundle/Tests/Functional/ImportExport/data/change_field_type.csv',
            'Error in row #1. Changing name or type of existing fields is not allowed.'
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
        $this->markTestSkipped(
            'This test will be completely removed and replaced with a set of smaller functional tests (see BAP-13063)'
        );
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

    private function doExportTemplate(): string
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

    private function validateImportFile(string $filePath, int $errorsCount = 0): void
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl(
                'oro_importexport_import_form',
                [
                    '_widgetContainer' => 'dialog',
                    'entity' => FieldConfigModel::class,
                    'importJob' => 'entity_fields_import_from_csv',
                    'options[entity_id]' => $this->entity->getId(),
                    'fileName' => $filePath,
                ]
            )
        );

        $this->assertHtmlResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertFileExists($filePath);

        $form = $crawler->selectButton('Submit')->form();
        $form['oro_importexport_import[file]']->upload($filePath);

        /** Change after BAP-1813 */
        $form->getFormNode()->setAttribute(
            'action',
            sprintf(
                '%s&entity=%s&importJob=entity_fields_import_from_csv&options[entity_id]=%d&_widgetContainer=dialog',
                $form->getFormNode()->getAttribute('action'),
                FieldConfigModel::class,
                $this->entity->getId()
            )
        );

        $this->client->followRedirects(true);
        $this->client->submit($form);

        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);

        $this->assertEquals($errorsCount, $this->client->getCrawler()->filter('.import-errors')->count());
    }

    private function doImport(int $added, int $replaced): void
    {
        $this->client->followRedirects(false);
        $this->ajaxRequest(
            'POST',
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
                'message' => 'Import started successfully. You will receive an email notification upon completion.'
            ],
            $data
        );
    }

    private function assertErrors(string $path, array|string $errorMessages): void
    {
        $this->validateImportFile($this->getFilePath($path), 1);

        $errors = $this->client->getCrawler()->filter('.import-errors')->html();

        foreach ((array)$errorMessages as $message) {
            self::assertStringContainsString($message, $errors);
        }
    }

    /**
     * @return FieldConfigModel[]
     */
    private function getEntityFields(): array
    {
        /** @var FieldConfigModel[] $fields */
        $fields = $this->getRepository(FieldConfigModel::class)->findBy(['entity' => $this->entity]);
        $result = [];

        foreach ($fields as $field) {
            $result[$field->getId()] = $field;
        }

        return $result;
    }

    private function getRepository(string $className): EntityRepository
    {
        return $this->getContainer()->get('doctrine')->getRepository($className);
    }

    private function getFilePath(string $file): string
    {
        return $this->getContainer()->get('file_locator')->locate($file);
    }
}
