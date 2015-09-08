<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Functional\Form;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class FieldTypeTest extends WebTestCase
{
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
    }

    /**
     * Test that ExtendFieldType override fixes the OroCRM bug
     */
    public function testCreateNewFieldFormWorks()
    {
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_entityextend_field_create', ['id' => 1])
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
}
