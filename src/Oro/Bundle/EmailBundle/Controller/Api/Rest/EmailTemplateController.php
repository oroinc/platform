<?php

namespace Oro\Bundle\EmailBundle\Controller\Api\Rest;

use FOS\Rest\Util\Codes;
use FOS\RestBundle\Controller\Annotations\Get as GetRoute;
use FOS\RestBundle\Controller\Annotations\NamePrefix;
use FOS\RestBundle\Controller\Annotations\RouteResource;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

use Oro\Bundle\EmailBundle\Provider\VariablesProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;

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
     * @return Response
     */
    public function deleteAction($id)
    {
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
     *
     * @ApiDoc(
     *     description="Get templates by entity name",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_index")
     * @GetRoute(requirements={"entityName"="(^[^\d][.]+)"})
     *
     * @return Response
     */
    public function getAction($entityName = null)
    {
        if (!$entityName) {
            return $this->handleView(
                $this->view(null, Codes::HTTP_NOT_FOUND)
            );
        }
        $entityName = str_replace('_', '\\', $entityName);

        /** @var $emailTemplateRepository EmailTemplateRepository */
        $emailTemplateRepository = $this->getDoctrine()->getRepository('OroEmailBundle:EmailTemplate');
        $templates               = $emailTemplateRepository->getTemplateByEntityName($entityName);

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
     * @GetRoute()
     * @return Response
     */
    public function getAvailableVariablesAction()
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
     * @param int           $entityId       id of an entity defined by $emailTemplate->getEntityName() class
     *
     * @ApiDoc(
     *     description="Get email template subject, type and content",
     *     resource=true
     * )
     * @AclAncestor("oro_email_emailtemplate_view")
     * @GetRoute("/emailtemplates/{id}/{entityId}", requirements={"id" = "\d+","entityId" = "^(\s*|\d+)$"})
     * @ParamConverter("emailTemplate", class="OroEmailBundle:EmailTemplate")
     *
     * @return Response
     */
    public function getTemplateAction(EmailTemplate $emailTemplate, $entityId = null)
    {
        $templateParams = [];
        if ($entityId && $emailTemplate->getEntityName()) {
            $entity = $this->getDoctrine()
                ->getRepository($emailTemplate->getEntityName())
                ->find($entityId);

            $templateParams['entity'] = $entity;
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
     * Get entity Manager
     *
     * @return ApiEntityManager
     */
    public function getManager()
    {
        return $this->get('oro_email.manager.emailtemplate.api');
    }

    /**
     * @return FormInterface
     */
    public function getForm()
    {
        return $this->get('oro_email.form.type.emailtemplate.api');
    }

    /**
     * @return ApiFormHandler
     */
    public function getFormHandler()
    {
        return $this->get('oro_email.form.handler.emailtemplate.api');
    }
}
