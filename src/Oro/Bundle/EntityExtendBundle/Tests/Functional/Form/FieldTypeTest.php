<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Form;

use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class FieldTypeTest extends WebTestCase
{
    /** @var int */
    protected $contactEntityId;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]),
            true
        );

        $this->contactEntityId = $this->getEntityIdFromGrid('Contact', 'OroCRMContactBundle');
    }

    /**
     * Test should check original FieldType form produce exception
     *
     * This test should fail, once bug will be fixed
     */
    public function testCreateNewFieldFailed()
    {
        $this->markTestSkipped('The test is skiped as result of the fix, ' .
            'for demonstration purposes, assumes that contact has many-to-one addresses extend field.');

        // re-initialize client in order to manipulate with clear container
        $this->initClient(
            [],
            array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]),
            true
        );

        $container = $this->getContainer();
        $fieldTypeService = 'oro_entity_extend.type.field';

        $overriddenFormType = $this->replaceFieldTypeService(
            $fieldTypeService,
            new FieldType(
                $container->get('oro_entity_config.config_manager'),
                $container->get('translator.default'),
                $container->get('oro_migration.db_id_name_generator')
            )
        );

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => $this->contactEntityId])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 500);

        $title = $crawler->filter('title')->text();
        $this->assertEquals(
            'A model for "OroCRM\Bundle\ContactBundle\Entity\ContactAddress::contact_addresses" ' .
            'was not found (500 Internal Server Error)',
            trim($title),
            'Failed asserting that form returned an error.'
        );

        // return container to it's previous state
        $this->replaceFieldTypeService($fieldTypeService, $overriddenFormType);
    }

    /**
     * Test that ExtendFieldType override fixes the OroCRM bug
     */
    public function testCreateNewFieldFormWorks()
    {
        $contactEntityId = $this->getEntityIdFromGrid('Contact', 'OroCRMContactBundle');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => $contactEntityId])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $relationChoices = $crawler->filter('#oro_entity_extend_field_type_type > optgroup:nth-child(3) > option')
            ->extract(['_text']);
        $expectedRelationChoices = ['Many to many', 'Many to one', 'One to many'];
        $this->assertEquals(
            $expectedRelationChoices,
            $relationChoices,
            'Failed asserting that relation choices are correct'
        );
    }

    /**
     * Test that reverse relation still visible on the counter-part (e.g. ContactAddress)
     */
    public function testReverseFieldWorks()
    {
        $this->markTestSkipped(
            'For demonstration purposes, assumes that contact has many-to-one addresses extend field.'
        );

        $contactAddressEntityId = $this->getEntityIdFromGrid('Contact', 'OroCRMContactBundle');

        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => $contactAddressEntityId])
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $relationChoices = $crawler->filter('#oro_entity_extend_field_type_type > optgroup:nth-child(3) > option')
            ->extract(['_text']);
        $expectedRelationChoices = ['Many to many', 'Many to one', 'One to many', 'Reuse "Contact Address" of Contact'];
        $this->assertEquals(
            $expectedRelationChoices,
            $relationChoices,
            'Failed asserting that relation choices are correct'
        );
    }

    /**
     * Replace field type in FormRegistry
     *
     * @param string $serviceName
     * @param mixed  $newService
     *
     * @return object returns old field type
     */
    protected function replaceFieldTypeService($serviceName, $newService)
    {
        $baseFieldType = $this->getContainer()->get($serviceName);

        $this->getContainer()->set($serviceName, $newService);

        return $baseFieldType;
    }

    /**
     * @param string $entityName
     * @param string $entityModule
     *
     * @return int
     */
    protected function getEntityIdFromGrid($entityName, $entityModule)
    {
        $gridName = 'entityconfig-grid';
        $response = $this->client->requestGrid(['gridName' => $gridName]);
        $result   = $this->getJsonResponseContent($response, 200);

        return array_reduce(
            $result['data'],
            function ($carry, $item) use ($entityName, $entityModule) {
                if ($item['entity_config_entity_name'] == $entityName &&
                    $item['entity_config_module_name'] == $entityModule) {
                    $carry = $item['id'];
                }

                return $carry;
            },
            null
        );
    }
}
