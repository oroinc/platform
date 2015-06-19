<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailSearchApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

/**
 * @RouteResource("email_search_relation")
 * @NamePrefix("oro_api_")
 */
class EmailSearchController extends RestGetController
{
    const ACTIVITY_EMAILS = 'emails';

    /**
     * Searches entities associated with the specified type of an activity entity.
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
     *      name="from",
     *      requirements=".+",
     *      nullable=true,
     *      description="The entity alias. One or several aliases separated by comma. Defaults to all entities"
     * )
     * @QueryParam(
     *      name="email",
     *      requirements=".+",
     *      nullable=true,
     *      description="An email address. Defaults to all emails"
     * )
     *
     * @ApiDoc(
     *      description="Searches entities associated with the specified type of an activity entity",
     *      resource=true
     * )
     * @return Response
     */
    public function cgetAction()
    {
        $manager = $this->getManager();
        $manager->setClass($manager->resolveEntityClass(self::ACTIVITY_EMAILS, true));

        $page  = (int)$this->getRequest()->get('page', 1);
        $limit = (int)$this->getRequest()->get('limit', self::ITEMS_PER_PAGE);

        $filters = [];
        $from    = $this->getRequest()->get('from', null);
        if ($from) {
            $filter          = new StringToArrayParameterFilter();
            $filters['from'] = $filter->filter($from, null);
        }

        $email = $this->getRequest()->get('email', null);
        if ($email) {
            /** @var EmailAddressHelper $emailAddressHelper */
            $emailAddressHelper = $this->container->get('oro_email.email.address.helper');
            $pureEmailAddress = $emailAddressHelper->extractPureEmailAddress($email);
            if ($pureEmailAddress) {
                $filters['email'] = $pureEmailAddress;
            }
        }

        //$filters
        return $this->handleGetListRequest($page, $limit, $filters);
    }

    /**
     * Gets the API entity manager
     *
     * @return EmailSearchApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.activity_search.api');
    }
}
