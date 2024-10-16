<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiCustomization;

use Extend\Entity\TestApiE1 as TestCustomEntity;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Functional\DataFixtures\LoadEnumsData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadBusinessUnit;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Symfony\Component\HttpFoundation\Response;

class NewApiFromScratchTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadOrganization::class,
            LoadBusinessUnit::class,
            LoadEnumsData::class,
            '@OroApiBundle/Tests/Functional/DataFixtures/new_api_entities.yml'
        ]);
    }

    #[\Override]
    protected function getRequestType(): RequestType
    {
        $requestType = parent::getRequestType();
        $requestType->add('test_new');

        return $requestType;
    }

    #[\Override]
    protected function request(
        string $method,
        string $uri,
        array $parameters = [],
        array $server = [],
        string $content = null
    ): Response {
        $server['HTTP_X-Test-Request-Type'] = 'test_new';

        return parent::request($method, $uri, $parameters, $server, $content);
    }

    public function testCustomEntitiesShouldBeRegisteredByDefault()
    {
        $entityType = $this->getEntityType(TestCustomEntity::class);
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@custom_entity1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => $entityType,
                    'id'         => '<toString(@custom_entity1->id)>',
                    'attributes' => [
                        'name' => 'Custom Entity 1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testEnumsShouldBeRegisteredByDefault()
    {
        $entityType = $this->getEntityType('Extend\Entity\EV_Api_Enum1');
        $response = $this->get(
            ['entity' => $entityType, 'id' => '<toString(@enum1_1->internalId)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => $entityType,
                    'id'   => '<toString(@enum1_1->internalId)>'
                ]
            ],
            $response
        );
    }

    public function testOtherEntitiesShouldNotBeRegistered()
    {
        $response = $this->cget(
            ['entity' => 'testapialldatatypes'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
