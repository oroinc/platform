<?php

namespace Oro\Bundle\WorkflowBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\UserBundle\Entity\AbstractUser;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

abstract class AbstractLoadEntityWorkflowDemoData extends AbstractFixture implements
    ContainerAwareInterface,
    DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * @return array|[<workflow1Name>, <workflow2Name>...]
     */
    abstract protected function getWorkflows();

    /**
     * @param object $entity
     * @param string $workflowName
     * @return AbstractUser
     */
    abstract protected function getEntityUser($entity, $workflowName);

    /**
     * @param objectManager $manager
     * @return array
     */
    abstract protected function getEntities(ObjectManager $manager);

    /**
     * @return int
     */
    abstract protected function getDeepLevel();

    /**
     * @return array|[<workflow1Name> => [<transition1Name>, <transition2Name>...]]
     */
    protected function getIgnoredTransitions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        //Backup Original Token
        $originalToken = $this->container->get('security.token_storage')->getToken();

        foreach ($this->getWorkflows() as $workflowName) {
            $this->generateTransitionsHistory($workflowName, $this->getEntities($manager));
        }

        //Restore Original Token
        $this->container->get('security.token_storage')->setToken($originalToken);
    }

    /**
     * @return WorkflowManager
     */
    private function getWorkflowManager()
    {
        return $this->container->get('oro_workflow.manager.system');
    }

    /**
     * @param object $entity
     * @param string $workflowName
     * @return WorkflowItem
     */
    protected function getWorkflowItem($entity, $workflowName)
    {
        return $this->getWorkflowManager()->getWorkflowItem($entity, $workflowName);
    }

    /**
     * @param WorkflowItem $workflowItem
     * @param Transition $transition
     */
    protected function transitWorkflow(WorkflowItem $workflowItem, Transition $transition)
    {
        $this->getWorkflowManager()->transit($workflowItem, $transition);
    }

    /**
     * @param string $workflowName
     * @param array $entities
     */
    protected function generateTransitionsHistory($workflowName, array $entities)
    {
        foreach ($entities as $entity) {
            $workflowItem = $this->getWorkflowItem($entity, $workflowName);

            if (null === ($user = $this->getEntityUser($entity, $workflowName))) {
                continue;
            }
            $this->setUserToken($user);
            $this->randomTransitionWalk($workflowItem, mt_rand(0, $this->getDeepLevel()));
        }
    }

    /**
     * @param $workflowItem
     * @param int $deepLevel
     */
    protected function randomTransitionWalk(WorkflowItem $workflowItem, $deepLevel)
    {
        $ignoredTransitions = $this->getIgnoredTransitions();
        if (isset($ignoredTransitions[$workflowItem->getWorkflowName()])) {
            $ignoreTransitions = $ignoredTransitions[$workflowItem->getWorkflowName()];
        } else {
            $ignoreTransitions = [];
        }

        while ($deepLevel-- > 0) {
            $transitions = $this->getWorkflowManager()->getTransitionsByWorkflowItem($workflowItem)
                ->filter(
                    function (Transition $transition) use ($ignoreTransitions) {
                        return !in_array($transition->getName(), $ignoreTransitions, true);
                    }
                )
                ->toArray();

            if (!count($transitions)) {
                break;
            }

            /* @var $transition Transition */
            $transition = $transitions[array_rand($transitions)];

            if ($this->getWorkflowManager()->isTransitionAvailable($workflowItem, $transition)) {
                $this->transitWorkflow($workflowItem, $transition);
            }
        }
    }

    /**
     * @param AbstractUser $user
     */
    private function setUserToken(AbstractUser $user)
    {
        /** @var Organization $organization */
        $organization = $user->getOrganization();

        $token = new UsernamePasswordOrganizationToken($user, false, 'main', $organization, $user->getRoles());
        $this->container->get('security.token_storage')->setToken($token);
    }
}
