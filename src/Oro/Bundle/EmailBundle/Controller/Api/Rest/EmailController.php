<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;
use FOS\RestBundle\Controller\Annotations\QueryParam;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;

use Oro\Bundle\EmailBundle\Entity\Manager\EmailApiEntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailFolder;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;

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
                        'content' => $value->getContent(),
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
