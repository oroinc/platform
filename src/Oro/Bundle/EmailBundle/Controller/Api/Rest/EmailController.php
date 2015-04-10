<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Doctrine\ORM\Tools\Export\ExportException;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Delete;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;

/**
 * @RouteResource("email")
 * @NamePrefix("oro_api_")
 */
class EmailController extends RestGetController
{
    /**
     * REST GET list
     *
     * @QueryParam(
     *      name="page",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Page number, starting from 1. Defaults to 1."
     * )
     * @QueryParam(
     *      name="limit",
     *      requirements="\d+",
     *      nullable=true,
     *      description="Number of items per page. Defaults to 10."
     * )
     * @ApiDoc(
     *      description="Get all emails",
     *      resource=true
     * )
     * @AclAncestor("oro_email_view")
     * @return Response
     */
    public function cgetAction()
    {
        $page = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        return $this->handleGetListRequest($page, $limit);
    }

    /**
     * @param integer $entityId Entity id
     *
     * @ApiDoc(
     *      description="Returns an AssociationList object",
     *      resource=true,
     *      statusCodes={
     *          200="Returned when successful",
     *          404="Activity association was not found",
     *      }
     * )
     * @AclAncestor("oro_email_view")
     * @return Response
     */
    public function getAssociationAction($entityId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);
        $associations = $entity->getActivityTargetEntities();

        return $this->handleView(
            $this->view($associations, is_array($associations) ? Codes::HTTP_OK : Codes::HTTP_NOT_FOUND)
        );
    }

    /**
     * Add new association
     *
     * @QueryParam(
     *      name="entityId",
     *      nullable=false,
     *      strict=true,
     *      description="Entity id"
     * )
     * @QueryParam(
     *      name="targetClassName",
     *      nullable=false,
     *      strict=true,
     *      description="Target class name"
     * )
     * @QueryParam(
     *      name="targetId",
     *      nullable=false,
     *      strict=true,
     *      description="Target Id"
     * )
     * @ApiDoc(
     *      description="Add new association",
     *      resource=true
     * )
     * @AclAncestor("oro_email_create")
     */
    public function postAssociationsAction()
    {
        /**
         * @var $entityRoutingHelper EntityRoutingHelper
         */
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');

        $entityId = $this->getRequest()->get('entityId');
        $targetClassName = $this->getRequest()->get('targetClassName');
        $targetId = $this->getRequest()->get('targetId');

        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);
        $targetClassName = $entityRoutingHelper->decodeClassName($targetClassName);
        try {
            if ($entity->supportActivityTarget($targetClassName)) {
                $target = $entityRoutingHelper->getEntity($targetClassName, $targetId);

                if (!$entity->hasActivityTarget($target)) {
                    $entity->addActivityTarget($target);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($entity);
                    $em->flush();

                    $view = $this->view($entity, Codes::HTTP_OK);
                } else {
                    $view = $this->view([], Codes::HTTP_ALREADY_REPORTED);
                }
            } else {
                $view = $this->view([], Codes::HTTP_NOT_ACCEPTABLE);
            }
        } catch (Exception $e) {
            $view = $this->view([], Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, Codes::HTTP_CREATED, ['entity' => $entity]);
    }

    /**
     * REST DELETE
     *
     * @param int $entityId
     * @param string $targetClassName
     * @param int $targetId
     *
     * @ApiDoc(
     *      description="Delete Association",
     *      resource=true
     * )
     * @AclAncestor("oro_email_delete")
     *
     * @Delete("/emails/{entityId}/associations/{targetClassName}/{targetId}")
     *
     * @return Response
     */
    public function deleteAssociationAction($entityId, $targetClassName, $targetId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);
        $entityRoutingHelper = $this->get('oro_entity.routing_helper');
        $em = $this->getDoctrine()->getManager();

        try {
            $target = $entityRoutingHelper->getEntity($targetClassName, $targetId);
            $entity->removeActivityTarget($target);
            $em->persist($entity);
            $em->flush();

            $view = $this->view($entity->getActivityTargetEntities(), Codes::HTTP_OK);
        } catch (\RuntimeException $e) {
            $view = $this->view([], Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, Codes::HTTP_LOOP_DETECTED, ['id' => $entityId, 'entity' => $entity]);
    }


    /**
     * REST DELETE
     *
     * @param int $entityId
     *
     * @ApiDoc(
     *      description="Delete Associations",
     *      resource=true
     * )
     * @return Response
     * @AclAncestor("oro_email_delete")
     *
     */
    public function deleteAssociationsAction($entityId)
    {
        /**
         * @var $entity Email
         */
        $entity = $this->getManager()->find($entityId);
        $associations = $entity->getActivityTargetEntities();
        $em = $this->getDoctrine()->getManager();
        try {
            foreach ($associations as $association) {
                $entity->removeActivityTarget(($association));
            }
            $em->persist($entity);
            $em->flush();

            $view = $this->view($entity->getActivityTargetEntities(), Codes::HTTP_OK);
        } catch (\RuntimeException $e) {
            $view = $this->view([], Codes::HTTP_BAD_REQUEST);
        }

        return $this->buildResponse($view, Codes::HTTP_LOOP_DETECTED, []);
    }

    /**
     * REST GET item
     *
     * @param string $id
     *
     * @ApiDoc(
     *      description="Get email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function transformEntityField($field, &$value)
    {
        switch ($field) {
            case 'fromEmailAddress':
                if ($value) {
                    /** @var EmailAddress $value */
                    $value = $value->getEmail();
                }
                break;
            case 'folder':
                if ($value) {
                    /** @var EmailFolder $value */
                    $value = $value->getFullName();
                }
                break;
            case 'emailBody':
                if ($value) {
                    /** @var EmailBody $value */
                    $value = array(
                        'content' => $value->getBodyContent(),
                        'isText' => $value->getBodyIsText(),
                        'hasAttachments' => $value->getHasAttachments(),
                    );
                }
                break;
            case 'recipients':
                if ($value) {
                    $result = array();
                    /** @var $recipient EmailRecipient */
                    foreach ($value as $index => $recipient) {
                        $result[$index] = array(
                            'name' => $recipient->getName(),
                            'type' => $recipient->getType(),
                            'emailAddress' => $recipient->getEmailAddress() ?
                                $recipient->getEmailAddress()->getEmail()
                                : null,
                        );
                    }
                    $value = $result;
                }
                break;
            default:
                parent::transformEntityField($field, $value);
        }
    }

    /**
     * Get entity manager
     *
     * @return EmailApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email.api');
    }
}
