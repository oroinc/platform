<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivitySearchApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;

/**
 * @RouteResource("email_search_relation")
 * @NamePrefix("oro_api_")
 */
class EmailActivitySearchController extends RestGetController
{
    /**
     * The type of the emails activity entity.
     *
     * @see Oro\Bundle\ActivityBundle\Controller\Api\Rest\ActivitySearchController::cgetAction
     */
    const EMAILS_ACTIVITY_TYPE = 'emails';

    /**
     * Searches entities associated with the emails activity type.
     *
     * @Get("/activities/emails/relations/search", name="")
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
     *     name="search",
     *     requirements=".+",
     *     nullable=true,
     *     description="The search string."
     * )
     * @QueryParam(
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The entity alias. One or several aliases separated by comma. Defaults to all entities."
     * )
     * @QueryParam(
     *      name="email",
     *      requirements=".+",
     *      nullable=true,
     *      description="An email address. Defaults to all emails."
     * )
     *
     * @ApiDoc(
     *      description="Searches entities associated with the emails activity type.",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass(self::EMAILS_ACTIVITY_TYPE, true));

        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filters = [
            'search' => $this->getRequest()->get('search')
        ];
        $from    = $this->getRequest()->get('from', null);
        if ($from) {
            $filter          = new StringToArrayParameterFilter();
            $filters['from'] = $filter->filter($from, null);
        }

        $email = $this->getRequest()->get('email', null);
        if ($email) {
            /** @var EmailAddressHelper $emailAddressHelper */
            $emailAddressHelper = $this->container->get('oro_email.email.address.helper');
            $pureEmailAddress   = $emailAddressHelper->extractPureEmailAddress($email);
            if ($pureEmailAddress) {
                $filters['email'] = $pureEmailAddress;
            }
        }

        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * Gets the API entity manager
     *
     * @return EmailActivitySearchApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email_activity_search.api');
    }
}
