<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiSmokeDeleteList;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class DeleteListTest extends RestJsonApiTestCase
{
    use CheckSkippedEntityTrait;

    public function testDeleteList()
    {
        $this->runForEntities(function (string $entityClass, array $excludedActions) {
            if (in_array(ApiAction::DELETE_LIST, $excludedActions, true)
                || in_array(ApiAction::GET_LIST, $excludedActions, true)
            ) {
                return;
            }

            if ($this->isSkippedEntity($entityClass, ApiAction::DELETE_LIST)
                || $this->isSkippedEntity($entityClass, ApiAction::GET_LIST)
            ) {
                return;
            }

            $entityType = $this->getEntityType($entityClass);
            $response = $this->cget(['entity' => $entityType], ['page[size]' => 1], [], false);
            if ($response->getStatusCode() === 400) {
                $response = $this->cget(['entity' => $entityType], [], [], false);
            }
            self::assertApiResponseStatusCodeEquals($response, 200, $entityType, ApiAction::GET_LIST);
            self::assertResponseContentTypeEquals($response, self::JSON_API_CONTENT_TYPE);

            $content = self::jsonToArray($response->getContent());
            if (!empty($content['data'])) {
                $response = $this->cdelete(
                    ['entity' => $entityType],
                    ['filter' => ['id' => $content['data'][0]['id']]],
                    [],
                    false
                );
                self::assertApiResponseStatusCodeEquals(
                    $response,
                    [Response::HTTP_NO_CONTENT, Response::HTTP_FORBIDDEN],
                    $entityType,
                    'delete_list'
                );
            }
        });
    }
}
