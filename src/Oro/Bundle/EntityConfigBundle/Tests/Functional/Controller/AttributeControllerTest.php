<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\AbstractConfigControllerTest;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\DomCrawler\Form;

/**
 * @group dist
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AttributeControllerTest extends AbstractConfigControllerTest
{
    public function testIndex(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_index', ['alias' => $this->getTestEntityAlias()])
        );
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
    }

    public function fieldTypesProvider(): array
    {
        return [
            ['bigint'],
            ['boolean'],
            ['money'],
            ['date'],
            ['datetime'],
            ['decimal'],
            ['float'],
            ['integer'],
            ['percent'],
            ['smallint'],
            ['string'],
            ['text'],
        ];
    }

    /**
     * @dataProvider fieldTypesProvider
     */
    public function testCreateSimple(string $fieldType): void
    {
        $form = $this->processFirstStep($fieldType, 'name' . $fieldType);

        $this->finishAttributeCreation($form);
    }

    private function processFirstStep(string $fieldType, string $name): Form
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_create', ['alias' => $this->getTestEntityAlias()])
        );

        $continueButton = $crawler->selectButton('Continue');

        $form = $continueButton->form();
        $form['oro_entity_extend_field_type[fieldName]'] = $name;
        $form['oro_entity_extend_field_type[type]'] = $fieldType;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit(
            $form,
            [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
    }

    private function finishAttributeCreation(Form $form): void
    {
        $this->client->submit($form);
        $this->assertResponse();
    }

    private function assertResponse(): void
    {
        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Attribute was successfully saved', $result->getContent());
    }

    public function testCreateFile(): void
    {
        $form = $this->processFirstStep('file', 'file');

        $form['oro_entity_config_type[attachment][maxsize]'] = 10;

        $this->finishAttributeCreation($form);
    }

    public function testCreateFileExternallyStored(): void
    {
        $form = $this->processFirstStep('file', 'file_external');

        $form['oro_entity_config_type[attachment][is_stored_externally]'] = 1;

        $this->finishAttributeCreation($form);
    }

    public function testCreateImage(): void
    {
        $form = $this->processFirstStep('image', 'image');

        $form['oro_entity_config_type[attachment][maxsize]'] = 10;
        $form['oro_entity_config_type[attachment][width]'] = 100;
        $form['oro_entity_config_type[attachment][height]'] = 100;

        $this->finishAttributeCreation($form);
    }

    public function testCreateImageExternallyStored(): void
    {
        $form = $this->processFirstStep('image', 'image_external');

        $form['oro_entity_config_type[attachment][is_stored_externally]'] = 1;
        $form['oro_entity_config_type[attachment][width]'] = 100;
        $form['oro_entity_config_type[attachment][height]'] = 100;

        $this->finishAttributeCreation($form);
    }

    public function testCreateEnum(): void
    {
        $form = $this->processFirstStep('enum', 'enum');

        $formValues = $form->getPhpValues();
        $formValues['oro_entity_config_type']['enum']['enum_options'] = [
            [
                'label' => 'First',
                'priority' => 1,
            ],
        ];

        self::assertArrayHasKey('is_visible', $formValues['oro_entity_config_type']['datagrid']);
        self::assertNotEquals(3, $formValues['oro_entity_config_type']['datagrid']['is_visible']);
        $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();
    }

    public function testCreateMultiEnum(): void
    {
        $form = $this->processFirstStep('multiEnum', 'multiEnum');

        $formValues = $form->getPhpValues();
        $formValues['oro_entity_config_type']['enum']['enum_options'] = [
            [
                'label' => 'First',
                'priority' => 1,
            ],
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();
    }

    public function testCreateOneToManyRelation(): void
    {
        $form = $this->processFirstStep('oneToMany', 'oneToMany');

        $this->createSelectOneToMany($form);

        $this->finishAttributeCreation($form);
    }

    public function testCreateManyToOneRelation(): void
    {
        $form = $this->processFirstStep('manyToOne', 'manyToOne');

        $this->createSelectManyToOne($form);

        $this->finishAttributeCreation($form);
    }

    public function testCreateManyToManyRelation(): void
    {
        $form = $this->processFirstStep('manyToMany', 'manyToMany');

        $this->createSelectOneToMany($form);

        $this->finishAttributeCreation($form);
    }

    private function getFieldByName(string $name): ?FieldConfigModel
    {
        /** @var EntityManager $configManager */
        $configManager = self::getContainer()->get('doctrine')->getManager('config');

        $entityConfigRepository = $configManager->getRepository(EntityConfigModel::class);
        $entityConfigModel = $entityConfigRepository->findOneBy([
            'className' => TestActivityTarget::class,
        ]);

        return $configManager->getRepository(FieldConfigModel::class)->findOneBy([
            'entity' => $entityConfigModel,
            'fieldName' => $name,
        ]);
    }

    private function getFieldConfigByName(string $name, string $scope): ConfigInterface
    {
        return self::getContainer()->get('oro_entity_config.config_manager')
            ->getFieldConfig($scope, TestActivityTarget::class, $name);
    }

    /**
     * @depends testCreateFile
     */
    public function testUpdate(): void
    {
        $fieldConfigModel = $this->getFieldByName('file');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_update', ['id' => $fieldConfigModel->getId()])
        );

        $saveButton = $crawler->selectButton('Save');
        $form = $saveButton->form();

        $newLabel = 'NewFileLabel';
        $form['oro_entity_config_type[entity][label]'] = $newLabel;

        $this->client->submit(
            $form,
            [Router::ACTION_PARAMETER => $saveButton->attr('data-action')]
        );

        $result = $this->client->getResponse();
        self::assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('Attribute was successfully saved', $result->getContent());

        $fieldConfig = $this->getFieldConfigByName('file', 'entity');

        $translator = self::getContainer()->get('translator');

        self::assertEquals($translator->trans((string)$fieldConfig->get('label')), $newLabel);
    }

    /**
     * @depends testCreateFile
     */
    public function testDelete(): void
    {
        $fieldConfigModel = $this->getFieldByName('file');

        $this->ajaxRequest(
            'DELETE',
            $this->getUrl('oro_attribute_remove', ['id' => $fieldConfigModel->getId()])
        );

        $fieldConfig = $this->getFieldConfigByName('file', 'extend');

        /**
         * Because schema changes has not been applied, the field in state `New` should not change
         * to be completely deleted.
         */
        self::assertEquals('New', $fieldConfig->get('state'));
    }

    public function testRequiredProperties(): void
    {
        $form = $this->processFirstStep('string', 'newString');

        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'save_and_stay';

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();

        $requiredProperties = [
            [
                'scope' => 'attribute',
                'code' => 'searchable',
            ],
            [
                'scope' => 'attribute',
                'code' => 'filterable',
            ],
            [
                'scope' => 'attribute',
                'code' => 'sortable',
            ],
        ];

        foreach ($requiredProperties as $requiredProperty) {
            $filter = sprintf(
                "//div[contains(@class,'control-group-choice')]//*[@name='oro_entity_config_type[%s][%s]']",
                $requiredProperty['scope'],
                $requiredProperty['code']
            );
            self::assertEquals(1, $crawler->filterXPath($filter)->count(), $requiredProperty['code']);
        }
    }
}
