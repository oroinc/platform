<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Action;

use Oro\Bundle\ActionBundle\Tests\Functional\OperationAwareTestTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadItems;

class UpdateTest extends WebTestCase
{
    use OperationAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([LoadItems::class]);
    }

    public function testExecute()
    {
        $item = $this->getReference(LoadItems::ITEM1);
        $operationName = 'UPDATE';
        $entityId = $item->getId();
        $entityClass = Item::class;
        $this->client->request(
            'POST',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => $operationName,
                    'entityClass' => $entityClass,
                    'entityId' => $entityId,
                ]
            ),
            $this->getOperationExecuteParams($operationName, $entityId, $entityClass),
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $response = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('oro_test_item_update', ['id' => $item->getId()]),
                'pageReload' => true
            ],
            $response
        );
    }
}
