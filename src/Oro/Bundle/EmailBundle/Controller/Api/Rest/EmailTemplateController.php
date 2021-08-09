<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EntityBundle\Twig\Sandbox\VariablesProvider;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Response;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

/**
 * REST API controller for email templates.
 */
class EmailTemplateController extends RestController
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete email template",
     *      resource=true
     * )
     * @Acl(
     *      id="oro_email_emailtemplate_delete",
     *      type="entity",
     *      class="OroEmailBundle:EmailTemplate",
     *      permission="DELETE"
     * )
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var EmailTemplate $entity */
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        /**
         * Deny to remove system templates
         */
        if ($entity->getIsSystem()) {
            return $this->handleView($this->view(null, Response::HTTP_FORBIDDEN));
        }

        $em = $this->getManager()->getObjectManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(null, Response::HTTP_NO_CONTENT));
    }

    /**
     * REST GET templates by entity name
     *
     * @param string $entityName
     * @param bool   $includeNonEntity
     * @param bool   $includeSystemTemplates
     *
     * @ApiDoc(
     *     description="Get templates by entity name",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_index")
     *
     * @return Response
     */
    public function cgetAction($entityName = null, $includeNonEntity = false, $includeSystemTemplates = true)
    {
        if (!$entityName) {
            return $this->handleView($this->view(null, Response::HTTP_NOT_FOUND));
        }

        $entityName = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityName);

        /** @var EmailTemplateRepository $emailTemplateRepository */
        $emailTemplateRepository = $this->getDoctrine()->getRepository('OroEmailBundle:EmailTemplate');

        $templates = $emailTemplateRepository
            ->getTemplateByEntityName(
                $this->get('oro_security.acl_helper'),
                $entityName,
                $this->get('oro_security.token_accessor')->getOrganization(),
                (bool)$includeNonEntity,
                (bool)$includeSystemTemplates
            );

        $serializedTemplates = [];
        foreach ($templates as $template) {
            $serializedTemplates[] = $this->serializeEmailTemplate($template);
        }

        return $this->handleView($this->view($serializedTemplates, Response::HTTP_OK));
    }

    /**
     * REST GET available variables
     *
     * @ApiDoc(
     *     description="Get available variables",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_view")
     *
     * @return Response
     */
    public function getVariablesAction()
    {
        /** @var VariablesProvider $provider */
        $provider = $this->get('oro_email.emailtemplate.variable_provider');

        $data = [
            'system' => $provider->getSystemVariableDefinitions(),
            'entity' => $provider->getEntityVariableDefinitions()
        ];

        return $this->handleView($this->view($data, Response::HTTP_OK));
    }

    /**
     * REST GET email template
     *
     * @param EmailTemplate $emailTemplate  comes from request parameter {id}
     *                                      that transformed to entity by param converter
     * @param int           $entityId       entity id of class defined by $emailTemplate->getEntityName()
     *
     * @ApiDoc(
     *     description="Get email template subject, type and content",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_view")
     * @ParamConverter("emailTemplate", class="OroEmailBundle:EmailTemplate")
     *
     * @return Response
     */
    public function getCompiledAction(EmailTemplate $emailTemplate, $entityId = null)
    {
        $templateParams = [];

        if ($entityId && $emailTemplate->getEntityName()) {
            $entity = $this->getDoctrine()
                ->getRepository($emailTemplate->getEntityName())
                ->find($entityId);
            if ($entity) {
                $templateParams['entity'] = $entity;
            }
        }

        // no entity found, but entity name defined for template
        if ($emailTemplate->getEntityName() && !isset($templateParams['entity'])) {
            return $this->handleView(
                $this->view(
                    [
                        'message' => sprintf(
                            'entity %s with id=%d not found',
                            $emailTemplate->getEntityName(),
                            $entityId
                        )
                    ],
                    Response::HTTP_NOT_FOUND
                )
            );
        }

        try {
            [$subject, $body] = $this->get('oro_email.email_renderer')->compileMessage($emailTemplate, $templateParams);

            $view = $this->view(
                [
                    'subject' => $subject,
                    'body' => $body,
                    'type' => $emailTemplate->getType(),
                ],
                Response::HTTP_OK
            );
        } catch (SyntaxError|LoaderError|RuntimeError $e) {
            $view = $this->view(
                [
                    'reason' => $this->get('translator')->trans('oro.email.emailtemplate.failed_to_compile'),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        return $this->handleView($view);
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_email.manager.emailtemplate.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }

    protected function serializeEmailTemplate(EmailTemplate $template): array
    {
        return [
            'id'          => $template->getId(),
            'name'        => $template->getName(),
            'is_system'   => $template->getIsSystem(),
            'is_editable' => $template->getIsEditable(),
            'parent'      => $template->getParent(),
            'subject'     => $template->getSubject(),
            'content'     => $template->getContent(),
            'entity_name' => $template->getEntityName(),
            'type'        => $template->getType()
        ];
    }
}
