<?php

namespace Oro\Bundle\WorkflowBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;
use Oro\Bundle\UserBundle\Entity\Role;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * Adds a datagrid with workflow permissions to the role view and edit pages.
 */
class RolePageListener
{
    private TranslatorInterface $translator;
    private RequestStack $requestStack;

    public function __construct(TranslatorInterface $translator, RequestStack $requestStack)
    {
        $this->translator = $translator;
        $this->requestStack = $requestStack;
    }

    public function onUpdatePageRender(BeforeFormRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $route = $request->attributes->get('_route');
        $routeParameters = $request->attributes->get('_route_params');

        if (!\in_array($route, ['oro_action_widget_form', 'oro_user_role_update', 'oro_user_role_create'], true)) {
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

    public function onViewPageRender(BeforeViewRenderEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        if ($request->attributes->get('_route') !== 'oro_user_role_view') {
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

    private function addWorkflowAclDatagrid(
        array $pageData,
        Environment $twigEnvironment,
        Role $entity,
        bool $readOnly
    ): array {
        $entityBlockIndex = $readOnly ? 2 : 1;
        $dataBlocks = $pageData['dataBlocks'];
        $resultBlocks = [];
        foreach ($dataBlocks as $key => $dataBlock) {
            $resultBlocks[\is_int($key) && $key > $entityBlockIndex ? $key + 1 : $key] = $dataBlock;
            // insert Workflow ACL Grid block after the entity block
            if ($key === $entityBlockIndex) {
                $resultBlocks[] = [
                    'title'     => $this->translator->trans('oro.workflow.workflowdefinition.entity_plural_label'),
                    'subblocks' => [['data' => [$this->getRenderedGridHtml($twigEnvironment, $entity, $readOnly)]]]
                ];
            }
        }
        $pageData['dataBlocks'] = $resultBlocks;

        return $pageData;
    }

    private function getRenderedGridHtml(Environment $twigEnvironment, Role $entity, bool $readOnly): string
    {
        return $twigEnvironment->render(
            '@OroWorkflow/Datagrid/aclGrid.html.twig',
            ['entity' => $entity, 'isReadonly' => $readOnly]
        );
    }
}
