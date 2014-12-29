<?php

namespace Oro\Bundle\WorkflowBundle\Controller;

use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\WorkflowBundle\Entity\ProcessDefinition;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * @Route("/processdefinition")
 */
class ProcessDefinitionController extends Controller
{
    /**
     * @Route(name="oro_process_definition_index")
     * @Template
     * @Acl(
     *      id="oro_process_definition_view",
     *      type="entity",
     *      class="OroWorkflowBundle:ProcessDefinition",
     *      permission="VIEW"
     * )
     *
     * @return array
     */
    public function indexAction()
    {
        return array();
    }

    /**
     * @Route(
     *      "/view/{name}",
     *      name="oro_process_definition_view"
     * )
     * @AclAncestor("oro_process_definition_view")
     * @Template("OroWorkflowBundle:ProcessDefinition:view.html.twig")
     *
     * @param ProcessDefinition $processDefinition
     * @return array
     */
    public function viewAction(ProcessDefinition $processDefinition)
    {
        $triggers = $this->getRepository('OroWorkflowBundle:ProcessTrigger')
            ->findBy(array('definition' => $processDefinition));
        return array(
            'entity'   => $processDefinition,
            'triggers' => $triggers
        );
    }

    /**
     * @param string $entityName
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository($entityName)
    {
        return $this->getDoctrine()->getRepository($entityName);
    }
}
