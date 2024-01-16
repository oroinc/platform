<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * CRUD controller for ProcessDefinition entities.
 * @Route("/processdefinition")
 */
class ProcessDefinitionController extends AbstractController
{
    /**
     * @Route(name="oro_process_definition_index")
     * @Template
     * @Acl(
     *      id="oro_process_definition_view",
     *      type="entity",
     *      class="Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route(
     *      "/view/{name}",
     *      name="oro_process_definition_view"
     * )
     * @AclAncestor("oro_process_definition_view")
     * @Template("@OroWorkflow/ProcessDefinition/view.html.twig")
     *
     * @param ProcessDefinition $processDefinition
     * @return array
     */
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
     * @return \Doctrine\Persistence\ObjectRepository
     */
    protected function getRepository($entityName)
    {
        return $this->container->get('doctrine')->getRepository($entityName);
    }

    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            ['doctrine' => ManagerRegistry::class]
        );
    }
}
