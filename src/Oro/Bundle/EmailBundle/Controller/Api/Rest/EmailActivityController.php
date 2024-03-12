<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations\QueryParam;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EmailBundle\Entity\EmailRecipient;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityApiEntityManager;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestGetController;
use Oro\Bundle\SoapBundle\Request\Parameters\Filter\StringToArrayParameterFilter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * REST API controller for email activities.
 */
class EmailActivityController extends RestGetController
{
    /**
     * Get entities where an email found by specified filters is an activity.
     *
     * @ApiDoc(
     *      description="Get entities where an email found by specified filters is an activity",
     *      resource=true
     * )
     * @param Request $request
     * @return Response
     */
    #[QueryParam(
        name: 'page',
        requirements: '\d+',
        description: 'Page number, starting from 1. Defaults to 1.',
        nullable: true
    )]
    #[QueryParam(
        name: 'limit',
        requirements: '\d+',
        description: 'Number of items per page. Defaults to 10.',
        nullable: true
    )]
    #[QueryParam(
        name: 'messageId',
        requirements: '.+',
        description: "The email 'Message-ID' attribute. One or several message ids separated by comma.",
        nullable: true
    )]
    #[QueryParam(
        name: 'from',
        requirements: '.+',
        description: 'The email sender address. One or several addresses separated by comma.',
        nullable: true
    )]
    #[QueryParam(
        name: 'to',
        requirements: '.+',
        description: 'The email recipient address. One or several addresses separated by comma.',
        nullable: true
    )]
    #[QueryParam(
        name: 'cc',
        requirements: '.+',
        description: 'The email address of carbon copy recipient. One or several addresses separated by comma.',
        nullable: true
    )]
    #[QueryParam(
        name: 'bcc',
        requirements: '.+',
        description: 'The email address of blind carbon copy recipient. One or several addresses separated by comma.',
        nullable: true
    )]
    #[QueryParam(name: 'subject', requirements: '.+', description: 'The email subject.', nullable: true)]
    public function cgetByFiltersAction(Request $request)
    {
        $manager = $this->getManager();

        $emailId = $manager->findEmailId(
            $this->getFilterCriteria(
                array_diff($this->getSupportedQueryParameters(__FUNCTION__), ['emailId']),
                [
                    'messageId' => new StringToArrayParameterFilter(),
                    'from'      => new StringToArrayParameterFilter(),
                    'to'        => new StringToArrayParameterFilter(),
                    'cc'        => new StringToArrayParameterFilter(),
                    'bcc'       => new StringToArrayParameterFilter()
                ],
                [
                    'from' => 'fromEmailAddress.email',
                    'to'   => 'toAddress.email',
                    'cc'   => 'ccAddress.email',
                    'bcc'  => 'bccAddress.email'
                ]
            ),
            [
                'fromEmailAddress' => null,
                'toRecipients'     => [
                    'join' => 'recipients'
                ],
                'toAddress'        => [
                    'join' => 'toRecipients.emailAddress'
                ],
                'ccRecipients'     => [
                    'join'      => 'recipients',
                    'condition' => 'ccRecipients.type = \'' . EmailRecipient::CC . '\''
                ],
                'ccAddress'        => [
                    'join' => 'ccRecipients.emailAddress'
                ],
                'bccRecipients'    => [
                    'join'      => 'recipients',
                    'condition' => 'bccRecipients.type = \'' . EmailRecipient::BCC . '\''
                ],
                'bccAddress'       => [
                    'join' => 'bccRecipients.emailAddress'
                ]
            ]
        );

        if ($emailId === null) {
            return $this->buildResponse('', self::ACTION_READ, ['result' => null], Response::HTTP_NOT_FOUND);
        }

        $page     = (int)$request->get('page', 1);
        $limit    = (int)$request->get('limit', self::ITEMS_PER_PAGE);
        $criteria = $this->buildFilterCriteria(['id' => ['=', $emailId]]);

        return $this->handleGetListRequest($page, $limit, $criteria);
    }

    /**
     * Get entity manager
     *
     * @return EmailActivityApiEntityManager
     */
    public function getManager()
    {
        return $this->container->get('oro_email.manager.email_activity.api');
    }
}
