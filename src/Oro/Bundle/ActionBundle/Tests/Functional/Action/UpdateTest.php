<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Action;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;

/**
 * @dbIsolation
 */
class UpdateTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures([
            'Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems',
        ]);
    }

    public function testExecute()
    {
        $item = $this->getReference(LoadItems::ITEM1);

        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'UPDATE',
                    'entityClass' => 'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    'entityId' => $item->getId(),
                ]
            ),
            [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $response = json_decode($result->getContent(), true);

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_test_item_update', ['id' => $item->getId()])
            ],
            $response
        );
    }
}
