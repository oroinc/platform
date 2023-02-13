<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\RestPlainSmokeDeleteList;

use Oro\Bundle\ApiBundle\Request\ApiAction;
use Oro\Bundle\ApiBundle\Tests\Functional\CheckSkippedEntityTrait;
use Oro\Bundle\ApiBundle\Tests\Functional\RestPlainApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @group regression
 */
class DeleteListTest extends RestPlainApiTestCase
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

            $response = $this->request(
                'GET',
                $this->getUrl($this->getListRouteName(), ['entity' => $entityType, 'limit' => 1])
            );
            self::assertApiResponseStatusCodeEquals($response, Response::HTTP_OK, $entityType, 'get list');

            $content = self::jsonToArray($response->getContent());
            if (!empty($content)) {
                $idFieldName = $this->getEntityIdFieldName($entityClass, ApiAction::DELETE_LIST);
                if ($idFieldName) {
                    $response = $this->request(
                        'DELETE',
                        $this->getUrl(
                            $this->getListRouteName(),
                            ['entity' => $entityType, $idFieldName => $content[0][$idFieldName]]
                        )
                    );
                    self::assertApiResponseStatusCodeEquals(
                        $response,
                        [Response::HTTP_NO_CONTENT, Response::HTTP_FORBIDDEN],
                        $entityType,
                        'delete_list'
                    );
                }
            }
        });
    }

    private function getEntityIdFieldName(string $entityClass, string $action): ?string
    {
        $metadata = $this->getApiMetadata($entityClass, $action, true);
        if (null === $metadata) {
            return null;
        }

        $idFieldNames = $metadata->getIdentifierFieldNames();
        if (count($idFieldNames) !== 1) {
            return null;
        }

        return reset($idFieldNames);
    }
}
