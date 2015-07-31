<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Doctrine\ORM\EntityManager;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Util\Codes;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProvider;

/**
 * @RouteResource("email")
 * @NamePrefix("oro_api_")
 */
class EmailController extends RestController
{
    /**
     * Get emails.
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
     * @QueryParam(
     *     name="messageId",
     *     requirements=".+",
     *     nullable=true,
     *     description="The email 'Message-ID' attribute. One or several message ids separated by comma."
     * )
     * @ApiDoc(
     *      description="Get emails",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_view")
     * @return Response
     */
    public function cgetAction()
    {
        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filterParameters = [
            'messageId' => new StringToArrayParameterFilter()
        ];
        $criteria         = $this->getFilterCriteria(
            $this->getSupportedQueryParameters(__FUNCTION__),
            $filterParameters
        );

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get email.
     *
     * @param string $id
     *
     * @Get(
     *      "/emails/{id}",
     *      name="",
     *      requirements={"id"="\d+"}
     * )
     * @ApiDoc(
     *      description="Get email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_view")
     * @return Response
     */
    public function getAction($id)
    {
        return $this->handleGetRequest($id);
    }

    /**
     * Update email.
     *
     * @param int $id The id of the email
     *
     * @ApiDoc(
     *      description="Update email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_edit")
     * @return Response
     */
    public function putAction($id)
    {
        return $this->handleUpdateRequest($id);
    }

    /**
     * Create new email.
     *
     * @ApiDoc(
     *      description="Create new email",
     *      resource=true
     * )
     * @AclAncestor("oro_email_email_edit")
     */
    public function postAction()
    {
        return $this->handleCreateRequest();
    }

    /**
     * Get email context data.
     *
     * @param int $id The email id
     *
     * @ApiDoc(
     *      description="Get email context data",
     *      resource=true
     * )
     *
     * @AclAncestor("oro_email_email_view")
     *
     * @return Response
     */
    public function getContextAction($id)
    {
        /** @var Email $email */
        $email = $this->getManager()->find($id);
        if (!$email) {
            return $this->buildNotFoundResponse();
        }

        $result = $this->getManager()->getEmailContext($email);

        return $this->buildResponse($result, self::ACTION_LIST, ['result' => $result]);
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function getRecipientAutocompleteAction(Request $request)
    {
        $relatedEntity = null;

        $entityClass = $request->query->get('entityClass');
        $entityId = $request->query->get('entityId');
        if ($entityClass && $entityId) {
            $em = $this->getEntityManagerForClass($entityClass);
            $relatedEntity = $em->getReference($entityClass, $entityId);
        }

        $query = $request->query->get('query');
        if ($request->query->get('search_by_id', false)) {
            $results = [
                [
                    'id'   => $query,
                    'text' => $query,
                ],
            ];
        } else {
            $limit = $request->query->get('per_page', 100);
            $results = $this->getEmailRecipientsProvider()->getEmailRecipients($relatedEntity, $query, $limit);
        }

        return new Response(json_encode(['results' => $results]), Codes::HTTP_OK);
    }

    /**
     * Get email cache manager
     *
     * @return EmailCacheManager
     */
    protected function getEmailCacheManager()
    {
        return $this->container->get('oro_email.email.cache.manager');
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

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        return $this->get('oro_email.form.email.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        return $this->get('oro_email.form.handler.email.api');
    }

    /**
     * @param string $attribute
     * @param Email $email
     *
     * @return bool
     */
    protected function assertEmailAccessGranted($attribute, Email $email)
    {
        return $this->get('oro_security.security_facade')->isGranted($attribute, $email);
    }

    /**
     * @return EmailRecipientsProvider
     */
    protected function getEmailRecipientsProvider()
    {
        return $this->get('oro_email.email_recipients.provider');
    }

    /**
     * @param string $className
     *
     * @return EntityManager
     */
    protected function getEntityManagerForClass($className)
    {
        return $this->getDoctrine()->getManagerForClass($className);
    }
}
