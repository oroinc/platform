<?php

namespace Oro\Bundle\EntityBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class EntityDictionaryControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
    }

    public function testGetAliases()
    {
        $dictionaryProvider  = $this->getContainer()->get('oro_entity.dictionary_value_list_provider');
        $entityAliasResolver = $this->getContainer()->get('oro_entity.entity_alias_resolver');

        $entities = array_map(
            function ($className) use ($entityAliasResolver) {
                // convert to entity alias
                return $entityAliasResolver->getPluralAlias($className);
            },
            $dictionaryProvider->getSupportedEntityClasses()
        );
        foreach ($entities as $entity) {
            $this->client->request('GET', $this->getUrl('oro_api_get_dictionary_values', ['entity' => $entity]));
            $this->getJsonResponseContent($this->client->getResponse(), 200);
        }

    }
}
