<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Functional;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Tests\Functional\AbstractConfigControllerTest;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivityTarget;
use Oro\Bundle\UIBundle\Route\Router;

/**
 * @group dist
 */
class AttributeControllerTest extends AbstractConfigControllerTest
{
    public function testIndex()
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_index', ['alias' => $this->getTestEntityAlias()])
        );
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
    }

    /**
     * @return array
     */
    public function fieldTypesProvider()
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
            ['text']
        ];
    }

    /**
     * @dataProvider fieldTypesProvider
     * @param string $fieldType
     */
    public function testCreateSimple($fieldType)
    {
        $form = $this->processFirstStep($fieldType);

        $this->finishAttributeCreation($form);
    }


    /**
     * @param string $fieldType
     * @return \Symfony\Component\DomCrawler\Form
     */
    private function processFirstStep($fieldType)
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_create', ['alias' => $this->getTestEntityAlias()])
        );

        $continueButton = $crawler->selectButton('Continue');

        $form = $continueButton->form();
        $form['oro_entity_extend_field_type[fieldName]'] = 'name' . strtolower($fieldType);
        $form['oro_entity_extend_field_type[type]'] = $fieldType;
        $this->client->followRedirects(true);

        $crawler = $this->client->submit(
            $form,
            [Router::ACTION_PARAMETER => $continueButton->attr('data-action')]
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        return $crawler->selectButton('Save and Close')->form();
    }

    /**
     * @param \Symfony\Component\DomCrawler\Form $form
     */
    private function finishAttributeCreation($form)
    {
        $this->client->submit($form);
        $this->assertResponse();
    }

    private function assertResponse()
    {
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Attribute was successfully saved', $result->getContent());
    }

    public function testCreateFile()
    {
        $form = $this->processFirstStep('file');

        $form['oro_entity_config_type[attachment][maxsize]'] = 1000000;

        $this->finishAttributeCreation($form);
    }

    public function testCreateImage()
    {
        $form = $this->processFirstStep('image');

        $form['oro_entity_config_type[attachment][maxsize]'] = 1000000;
        $form['oro_entity_config_type[attachment][width]'] = 100;
        $form['oro_entity_config_type[attachment][height]'] = 100;

        $this->finishAttributeCreation($form);
    }

    public function testCreateEnum()
    {
        $form = $this->processFirstStep('enum');

        $formValues = $form->getPhpValues();
        $formValues['oro_entity_config_type']['enum']['enum_options'] = [
            [
                'label' => 'First',
                'priority' => 1
            ]
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();
    }

    public function testCreateMultiEnum()
    {
        $form = $this->processFirstStep('multiEnum');

        $formValues = $form->getPhpValues();
        $formValues['oro_entity_config_type']['enum']['enum_options'] = [
            [
                'label' => 'First',
                'priority' => 1
            ]
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);
        $this->assertResponse();
    }

    public function testCreateOneToManyRelation()
    {
        $form = $this->processFirstStep('oneToMany');

        $this->createSelectOneToMany($form);

        $this->finishAttributeCreation($form);
    }

    public function testCreateManyToOneRelation()
    {
        $form = $this->processFirstStep('manyToOne');

        $this->createSelectManyToOne($form);

        $this->finishAttributeCreation($form);
    }

    public function testCreateManyToManyRelation()
    {
        $form = $this->processFirstStep('manyToMany');

        $this->createSelectOneToMany($form);

        $this->finishAttributeCreation($form);
    }

    /**
     * @param string $name
     * @return object|FieldConfigModel
     */
    private function getFieldByName($name)
    {
        /** @var EntityManager $configManager */
        $configManager = $this->getContainer()->get('doctrine')->getManager('config');

        $entityConfigRepository = $configManager->getRepository(EntityConfigModel::class);
        $entityConfigModel = $entityConfigRepository->findOneBy([
            'className' => TestActivityTarget::class,
        ]);

        $fieldConfigRepository = $configManager->getRepository(FieldConfigModel::class);

        return $fieldConfigRepository->findOneBy([
            'entity' => $entityConfigModel,
            'fieldName' => $name
        ]);
    }

    /**
     * @param string $name
     * @param $scope
     * @return \Oro\Bundle\EntityConfigBundle\Config\ConfigInterface
     */
    private function getFieldConfigByName($name, $scope)
    {
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');
        return $configManager->getFieldConfig($scope, TestActivityTarget::class, $name);
    }

    /**
     * @depends testCreateFile
     */
    public function testUpdate()
    {
        $fieldConfigModel = $this->getFieldByName('namefile');

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
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('Attribute was successfully saved', $result->getContent());

        $fieldConfig = $this->getFieldConfigByName('namefile', 'entity');

        $translator = $this->getContainer()->get('translator');

        $this->assertEquals($translator->trans($fieldConfig->get('label')), $newLabel);
    }

    /**
     * @depends testCreateFile
     */
    public function testDelete()
    {
        $fieldConfigModel = $this->getFieldByName('namefile');

        $this->client->request(
            'GET',
            $this->getUrl('oro_attribute_remove', ['id' => $fieldConfigModel->getId()])
        );

        $fieldConfig = $this->getFieldConfigByName('namefile', 'extend');

        $this->assertEquals($fieldConfig->get('state'), 'Deleted');
    }
}
