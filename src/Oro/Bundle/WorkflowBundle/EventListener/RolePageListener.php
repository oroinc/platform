<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UserBundle\Entity\Role;

class RolePageListener
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var Request */
    protected $request;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param Request|null $request
     */
    public function setRequest(Request $request = null)
    {
        $this->request = $request;
    }

    /**
     * Adds rendered Workflows ACL datagrid block on edit role page.
     *
     * @param BeforeFormRenderEvent $event
     */
    public function onUpdatePageRender(BeforeFormRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        $route = $this->request->attributes->get('_route');
        $routeParameters = $this->request->attributes->get('_route_params');

        if (!in_array($route, ['oro_action_widget_form', 'oro_user_role_update', 'oro_user_role_create'], true)) {
            // not a manipulate role page
            return;
        }
        if ($route === 'oro_action_widget_form' && $routeParameters['operationName'] !== 'clone_role') {
            // not a manipulate role page
            return;
        }

        $event->setFormData(
            $this->addWorkflowAclDatagrid(
                $event->getFormData(),
                $event->getTwigEnvironment(),
                $event->getEntity() ?: $event->getForm()->vars['value'],
                false
            )
        );
    }

    /**
     * Adds rendered readonly Workflows ACL datagrid block on edit role page.
     *
     * @param BeforeViewRenderEvent $event
     */
    public function onViewPageRender(BeforeViewRenderEvent $event)
    {
        if (!$this->request) {
            return;
        }

        if ($this->request->attributes->get('_route') !== 'oro_user_role_view') {
            // we are not at view role page
            return;
        }

        $event->setData(
            $this->addWorkflowAclDatagrid(
                $event->getData(),
                $event->getTwigEnvironment(),
                $event->getEntity(),
                true
            )
        );
    }

    /**
     * Adds the Workflow ACL datagrid block to the page data and return updated data array.
     *
     * @param array             $pageData
     * @param \Twig_Environment $twigEnvironment
     * @param Role              $entity
     * @param boolean           $readOnly
     *
     * @return array
     */
    protected function addWorkflowAclDatagrid($pageData, \Twig_Environment $twigEnvironment, Role $entity, $readOnly)
    {
        $dataBlocks = $pageData['dataBlocks'];
        $resultBlocks = [];
        foreach ($dataBlocks as $id => $dataBlock) {
            $resultBlocks[] = $dataBlock;
            // insert Workflow ACL Grid block after the entity block
            if ($id === 2) {
                $resultBlocks[] = [
                    'title'     => $this->translator->trans('oro.workflow.workflowdefinition.entity_plural_label'),
                    'subblocks' => [
                        [
                            'data' => [
                                $this->getRenderedGridHtml($twigEnvironment, $entity, $readOnly)
                            ]
                        ]
                    ]
                ];
            }
        }

        $pageData['dataBlocks'] = $resultBlocks;

        return $pageData;
    }

    /**
     * Renders Datagrid html for given role
     *
     * @param \Twig_Environment $twigEnvironment
     * @param Role              $entity
     * @param boolean           $readOnly
     *
     * @return string
     */
    protected function getRenderedGridHtml(\Twig_Environment $twigEnvironment, Role $entity, $readOnly)
    {
        return $twigEnvironment->render(
            'OroWorkflowBundle:Datagrid:aclGrid.html.twig',
            [
                'entity'     => $entity,
                'isReadonly' => $readOnly
            ]
        );
    }
}
