<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\SecurityBundle\Attribute\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * CRUD controller for ProcessDefinition entities.
 */
#[Route(path: '/processdefinition')]
class ProcessDefinitionController extends AbstractController
{
    /**
     *
     * @return array
     */
    #[Route(name: 'oro_process_definition_index')]
    #[Template('@OroWorkflow/ProcessDefinition/index.html.twig')]
    #[Acl(id: 'oro_process_definition_view', type: 'entity', class: ProcessDefinition::class, permission: 'VIEW')]
    public function indexAction()
    {
        return [];
    }

    /**
     * @param ProcessDefinition $processDefinition
     * @return array
     */
    #[Route(path: '/view/{name}', name: 'oro_process_definition_view')]
    #[Template('@OroWorkflow/ProcessDefinition/view.html.twig')]
    #[AclAncestor('oro_process_definition_view')]
    public function viewAction(ProcessDefinition $processDefinition)
    {
        $triggers = $this->getRepository(ProcessTrigger::class)
            ->findBy(['definition' => $processDefinition]);
        return [
            'entity'   => $processDefinition,
            'triggers' => $triggers
        ];
    }

    /**
     * @param string $entityName
     * @return ObjectRepository
     */
    protected function getRepository($entityName)
    {
        return $this->container->get('doctrine')->getRepository($entityName);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
