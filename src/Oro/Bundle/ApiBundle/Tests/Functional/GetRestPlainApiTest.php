<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\ApiBundle\Request\RestRequest;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class GetRestPlainApiTest extends WebTestCase
{
    /** @var ContainerInterface */
    protected $container;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());
        $container                 = $this->getContainer();
        $this->entityAliasResolver = $container->get('oro_entity.entity_alias_resolver');
        $this->doctrineHelper      = $container->get('oro_api.doctrine_helper');
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetListRestRequests($entityClass)
    {
        $entityAlias = $this->entityAliasResolver->getPluralAlias($entityClass);

        //@todo: should be deleted after voter was fixed
        if ($entityAlias === 'abandonedcartcampaigns') {
            $this->markTestSkipped('Should be deleted after abandonedcartcampaigns voter was fixed');
        }

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'page[size]' => 1])
        );
        $response = $this->client->getResponse();
        $this->checkResponseStatus($response, 200, $entityAlias, 'get list');

        // test get request
        $content = $this->jsonToArray($response->getContent());
        list($id, $recordExist) = $this->getGetRequestConfig($entityClass, $content);

        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_get', ['entity' => $entityAlias, 'id' => $id])
        );
        $this->checkResponseStatus($this->client->getResponse(), $recordExist ? 200 : 404, $entityAlias, 'get');
    }

    /**
     * @return array
     */
    public function getEntities()
    {
        $this->initClient();
        $entities                = [];
        $container               = $this->getContainer();
        $entityManagers          = $container->get('oro_entity_config.entity_manager_bag')->getEntityManagers();
        $entityExclusionProvider = $container->get('oro_api.entity_exclusion_provider');
        foreach ($entityManagers as $em) {
            $allMetadata = $em->getMetadataFactory()->getAllMetadata();
            foreach ($allMetadata as $metadata) {
                if ($metadata->isMappedSuperclass) {
                    continue;
                }
                if ($entityExclusionProvider->isIgnoredEntity($metadata->name)) {
                    continue;
                }
                $entities[$metadata->name] = [$metadata->name];
            }
        }

        return $entities;
    }

    /**
     * @param string $entityClass
     * @param array  $content
     *
     * @return array
     */
    protected function getGetRequestConfig($entityClass, $content)
    {
        $recordExist   = count($content) === 1;
        $recordContent = $recordExist ? $content [0] : [];
        $idFields      = $this->doctrineHelper->getEntityIdentifierFieldNamesForClass($entityClass);
        $idFieldCount  = count($idFields);
        if ($idFieldCount === 1) {
            // single identifier
            return [$recordExist ? $recordContent[reset($idFields)] : 1, $recordExist];
        } elseif ($idFieldCount > 1) {
            // combined identifier
            $requirements = [];
            foreach ($idFields as $field) {
                $requirements[$field] = $recordExist ? $content[$field] : 1;
            }

            return [implode(RestRequest::ARRAY_DELIMITER, $requirements), $recordExist];
        }
    }

    /**
     * @param Response $response
     * @param integer  $statusCode
     * @param string   $entityName
     * @param string   $requestType
     */
    protected function checkResponseStatus($response, $statusCode, $entityName, $requestType)
    {
        try {
            $this->assertResponseStatusCodeEquals($response, $statusCode);
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $e = new \PHPUnit_Framework_ExpectationFailedException(
                sprintf(
                    'Wrong %s response for "%s" request for entity: "%s". Error message: %s',
                    $statusCode,
                    $requestType,
                    $entityName,
                    $e->getMessage()
                ),
                $e->getComparisonFailure()
            );
            throw $e;
        }
    }
}
