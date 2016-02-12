<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\DataAuditBundle\Entity\Audit;

/**
 * @dbIsolation
 */
class GetRestJsonApiWithTableInheritanceTest extends ApiTestCase
{
    /**
     * FQCN of the entity being used for testing.
     */
    const ENTITY_CLASS = 'Oro\Bundle\DataAuditBundle\Entity\AuditField';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        parent::setUp();

        $this->loadFixtures(['Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadAuditData']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequestType()
    {
        return [RequestType::REST, RequestType::JSON_API];
    }

    /**
     * @param array $params
     * @param array $expects
     *
     * @dataProvider getParamsAndExpectation
     */
    public function testGetEntityWithTableInheritance($params, $expects)
    {
        /** @var Audit $auditLogEntry */
        $auditLogEntry = $this->getReference('audit_log_entry');

        $expects['data'][0]['id'] = (string)$auditLogEntry->getField('username')->getId();

        $expects['data'][0]['relationships']['audit']['data']['id'] = (string)$auditLogEntry->getId();
        if (isset($expects['included'][0]['id'])) {
            $expects['included'][0]['id'] = (string)$auditLogEntry->getId();

        }

        $entityAlias = $this->entityClassTransformer->transform(self::ENTITY_CLASS);

        // test get list request
        $this->client->request(
            'GET',
            $this->getUrl('oro_rest_api_cget', ['entity' => $entityAlias, 'page[size]' => 1]),
            $params,
            [],
            array_replace(
                $this->generateWsseAuthHeader(),
                ['CONTENT_TYPE' => 'application/vnd.api+json']
            )
        );

        $response = $this->client->getResponse();

        $this->assertApiResponseStatusCodeEquals($response, 200, $entityAlias, 'get list');
        $this->assertEquals($expects, json_decode($response->getContent(), true));
    }

    /**
     * @return array
     */
    public function getParamsAndExpectation()
    {
        return [
            'Related entity with table inheritance'            => [
                'params'  => [
                    'fields' => [
                        'auditfields' => 'oldText,newText,audit'
                    ],
                    'sort'   => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_1.yml')
            ],
            'Related entity with table inheritance (expanded)' => [
                'params'  => [
                    'include' => 'audit',
                    'fields'  => [
                        'auditfields' => 'oldText,newText,audit',
                        'audit'       => 'objectClass'
                    ],
                    'sort'    => '-id'
                ],
                'expects' => $this->loadExpectation('output_inheritance_2.yml')
            ],
        ];
    }
}
