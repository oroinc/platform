<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\RestBundle\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Provider\VariablesProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;

/**
 * @RouteResource("emailtemplate")
 * @NamePrefix("oro_api_")
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
     * @Delete(requirements={"id"="\d+"})
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        /** @var EmailTemplate $entity */
        $entity = $this->getManager()->find($id);
        if (!$entity) {
            return $this->handleView($this->view(null, Codes::HTTP_NOT_FOUND));
        }

        /**
         * Deny to remove system templates
         */
        if ($entity->getIsSystem()) {
            return $this->handleView($this->view(null, Codes::HTTP_FORBIDDEN));
        }

        $em = $this->getManager()->getObjectManager();
        $em->remove($entity);
        $em->flush();

        return $this->handleView($this->view(null, Codes::HTTP_NO_CONTENT));
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
     * @Get("/emailtemplates/list/{entityName}/{includeNonEntity}/{includeSystemTemplates}",
     *      requirements={"entityName"="\w+", "includeNonEntity"="\d+", "includeSystemTemplates"="\d+"},
     *      defaults={"entityName"=null, "includeNonEntity"=false, "includeSystemTemplates"=true}
     * )
     *
     * @return Response
     */
    public function cgetAction($entityName = null, $includeNonEntity = false, $includeSystemTemplates = true)
    {
        if (!$entityName) {
            return $this->handleView(
                $this->view(null, Codes::HTTP_NOT_FOUND)
            );
        }

        $securityContext = $this->get('security.context');
        /** @var UsernamePasswordOrganizationToken $token */
        $token        = $securityContext->getToken();
        $organization = $token->getOrganizationContext();

        $entityName = $this->get('oro_entity.routing_helper')->resolveEntityClass($entityName);

        /** @var $emailTemplateRepository EmailTemplateRepository */
        $emailTemplateRepository = $this->getDoctrine()->getRepository('OroEmailBundle:EmailTemplate');
        $templates = $emailTemplateRepository
            ->getTemplateByEntityName(
                $entityName,
                $organization,
                (bool)$includeNonEntity,
                (bool)$includeSystemTemplates
            );

        return $this->handleView(
            $this->view($templates, Codes::HTTP_OK)
        );
    }

    /**
     * REST GET available variables
     *
     * @ApiDoc(
     *     description="Get available variables",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_view")
     * @Get("/emailtemplates/variables")
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

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
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
     * @Get("/emailtemplates/compiled/{id}/{entityId}",
     *      requirements={"id"="\d+", "entityId"="\d*"}
     * )
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
                    Codes::HTTP_NOT_FOUND
                )
            );
        }

        list($subject, $body) = $this->get('oro_email.email_renderer')
            ->compileMessage($emailTemplate, $templateParams);

        $data = [
            'subject' => $subject,
            'body'    => $body,
            'type'    => $emailTemplate->getType(),
        ];

        return $this->handleView(
            $this->view($data, Codes::HTTP_OK)
        );
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
}
