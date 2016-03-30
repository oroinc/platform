<?php

namespace Oro\Bundle\EmailBundle\Controller\Configuration;

use FOS\RestBundle\Controller\Annotations\Delete;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\FormBundle\Model\AutocompleteRequest;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

/**
 * Class MailboxController
 *
 * Actions in this controller are protected by MailboxAuthorizationListener because access to them is determined
 * by access to Organization entity which is not even always available.
 * @see Oro\Bundle\EmailBundle\EventListener\MailboxAuthorizationListener
 *
 * @package Oro\Bundle\EmailBundle\Controller\Configuration
 */
class MailboxController extends Controller
{
    const ACTIVE_GROUP    = 'platform';
    const ACTIVE_SUBGROUP = 'email_configuration';

    /**
     * @Route(
     *      "/mailbox/update/{id}",
     *      name="oro_email_mailbox_update"
     * )
     * @ParamConverter(
     *      "mailbox",
     *      class="OroEmailBundle:Mailbox"
     * )
     * @Template
     *
     * @param Mailbox $mailbox
     * @param Request $request
     *
     * @return array
     */
    public function updateAction(Mailbox $mailbox, Request $request)
    {
        return $this->update($mailbox, $request);
    }

    /**
     * Prepares and handles data of Mailbox update/create form.
     *
     * @param Mailbox $mailbox
     * @param Request $request
     *
     * @return array
     */
    private function update(Mailbox $mailbox, Request $request)
    {
        $provider = $this->get('oro_config.provider.system_configuration.form_provider');

        list($activeGroup, $activeSubGroup) = $provider->chooseActiveGroups(self::ACTIVE_GROUP, self::ACTIVE_SUBGROUP);

        $tree = $provider->getTree();

        $handler = $this->get('oro_email.form.handler.mailbox');

        if ($handler->process($mailbox)) {
            $this->get('session')->getFlashBag()->add(
                'success',
                $this->get('translator')->trans(
                    'oro.email.mailbox.action.saved',
                    ['%mailbox%' => $mailbox->getLabel()]
                )
            );

            return $this->get('oro_ui.router')->redirectAfterSave(
                [
                    'route' => 'oro_email_mailbox_update',
                    'parameters' => ['id' => $mailbox->getId()]
                ],
                $this->getRedirectData($request),
                $mailbox
            );
        }

        return [
            'data'           => $tree,
            'form'           => $handler->getForm()->createView(),
            'activeGroup'    => $activeGroup,
            'activeSubGroup' => $activeSubGroup,
            'redirectData'   => $this->getRedirectData($request),
        ];
    }

    /**
     * @Route(
     *      "/mailbox/create/{organization_id}",
     *      name="oro_email_mailbox_create",
     *      defaults={"organization_id"=null}
     * )
     * @ParamConverter(
     *      "organization",
     *      class="OroOrganizationBundle:Organization",
     *      isOptional=true,
     *      options={"id"="organization_id"}
     * )
     * @Template("OroEmailBundle:Configuration/Mailbox:update.html.twig")
     *
     * @param Request      $request
     * @param Organization $organization
     *
     * @return array
     */
    public function createAction(Request $request, Organization $organization = null)
    {
        $data = new Mailbox();
        if ($organization != null) {
            $data->setOrganization($organization);
        } else {
            $data->setOrganization($this->get('oro_security.security_facade')->getOrganization());
        }

        return $this->update($data, $request);
    }

    /**
     * @Delete(
     *      "/mailbox/delete/{id}",
     *      name="oro_email_mailbox_delete"
     * )
     * @ParamConverter(
     *      "mailbox",
     *      class="OroEmailBundle:Mailbox"
     * )
     *
     * @param Mailbox $mailbox
     *
     * @return Response
     */
    public function deleteAction(Mailbox $mailbox)
    {
        $mailboxManager = $this->getDoctrine()->getManagerForClass('OroEmailBundle:Mailbox');
        $mailboxManager->remove($mailbox);
        $mailboxManager->flush();

        return new Response(Response::HTTP_OK);
    }

    /**
     * This is a separate route for user searing within mailbox organization.
     *
     * @Route(
     *      "/mailbox/users/search/{organizationId}",
     *      name="oro_email_mailbox_users_search"
     * )
     *
     * @param Request $request
     * @param int     $organizationId
     *
     * @return JsonResponse
     */
    public function searchUsersAction(Request $request, $organizationId)
    {
        $autocompleteRequest = new AutocompleteRequest($request);
        $validator           = $this->get('validator');
        $isXmlHttpRequest    = $request->isXmlHttpRequest();
        $code                = 200;
        $result              = [
            'results' => [],
            'hasMore' => false,
            'errors'  => []
        ];

        if ($violations = $validator->validate($autocompleteRequest)) {
            foreach ($violations as $violation) {
                $result['errors'][] = $violation->getMessage();
            }
        }

        if (!$this->get('oro_form.autocomplete.security')->isAutocompleteGranted($autocompleteRequest->getName())) {
            $result['errors'][] = 'Access denied.';
        }

        if (!empty($result['errors'])) {
            if ($isXmlHttpRequest) {
                return new JsonResponse($result, $code);
            }

            throw new HttpException($code, implode(', ', $result['errors']));
        }

        $searchHandler = $this->get('oro_email.autocomplete.mailbox_user_search_handler');
        $searchHandler->setOrganizationId($organizationId);

        return new JsonResponse(
            $searchHandler->search(
                $autocompleteRequest->getQuery(),
                $autocompleteRequest->getPage(),
                $autocompleteRequest->getPerPage(),
                $autocompleteRequest->isSearchById()
            )
        );
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    protected function getRedirectData(Request $request)
    {
        return $request->query->get(
            'redirectData',
            [
                'route' => 'oro_config_configuration_system',
                'parameters' => [
                    'activeGroup' => self::ACTIVE_GROUP,
                    'activeSubGroup' => self::ACTIVE_SUBGROUP,
                ]
            ]
        );
    }
}
