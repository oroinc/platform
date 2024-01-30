<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\ActivityBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailThreadActivityContextApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Handler\DeleteHandler;
use Oro\Bundle\SoapBundle\Model\RelationIdentifier;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller to manage activity context data for email threads.
 */
class EmailThreadActivityContextController extends RestController
{
    /**
     * @ApiDoc(
     *      description="Get activity context data for an email thread",
     *      resource=true
     * )
     */
    public function getAction(int $id): Response
    {
        $result = $this->getManager()->getActivityContext(Email::class, $id);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * @ApiDoc(
     *      description="Deletes an association between an email thread and a target entity",
     *      resource=true
     * )
     */
    public function deleteAction(int $id, string $entity, mixed $entityId): Response
    {
        $relationId = new RelationIdentifier(
            Email::class,
            $id,
            $this->getManager()->resolveEntityClass($entity, true),
            $entityId
        );

        try {
            return $this->handleDeleteRequest($relationId);
        } catch (InvalidArgumentException $exception) {
            return $this->handleDeleteError($exception->getMessage(), Response::HTTP_BAD_REQUEST, $relationId);
        } catch (\Exception $e) {
            return $this->handleDeleteError($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, $relationId);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getManager(): EmailThreadActivityContextApiEntityManager
    {
        return $this->container->get('oro_email.manager.email_thread_activity_context.api');
    }

    /**
     * {@inheritDoc}
     */
    public function getFormHandler(): ApiFormHandler
    {
        throw new \BadMethodCallException();
    }

    /**
     * {@inheritDoc}
     */
    protected function getDeleteHandler(): DeleteHandler
    {
        return $this->container->get('oro_email.delete_handler.email_thread');
    }

    private function handleDeleteError(string $message, int $code, RelationIdentifier $id): Response
    {
        return $this->buildResponse(
            $this->view(['message' => $message], $code),
            self::ACTION_DELETE,
            [
                'ownerEntityClass'  => $id->getOwnerEntityClass(),
                'ownerEntityId'     => $id->getOwnerEntityId(),
                'targetEntityClass' => $id->getTargetEntityClass(),
                'targetEntityId'    => $id->getTargetEntityId(),
                'success'           => false
            ]
        );
    }
}
