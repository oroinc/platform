<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Exception\UnknownAttributeException;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowStepType;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;

class StepAssembler extends AbstractAssembler
{
    /**
     * @var Attribute[]
     */
    protected $attributes;

    /**
     * @var array
     */
    protected $stepEntities = array();

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        if (!$this->entityManager) {
            $this->entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        }

        return $this->entityManager;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param array $configuration
     * @param Attribute[]|Collection $attributes
     * @return ArrayCollection
     */
    public function assemble(WorkflowDefinition $workflowDefinition, array $configuration, $attributes)
    {
        $this->setAttributes($attributes);

        $steps = new ArrayCollection();
        foreach ($configuration as $stepName => $options) {
            $step = $this->assembleStep($workflowDefinition, $stepName, $options);
            $steps->set($stepName, $step);
        }

        $this->attributes = array();

        return $steps;
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param string $stepName
     * @param array $options
     * @return Step
     * @throws InvalidParameterException
     * @throws UnknownAttributeException
     */
    protected function assembleStep(WorkflowDefinition $workflowDefinition, $stepName, array $options)
    {
        $this->assertOptions($options, array('label'));

        $step = new Step();
        $step->setName($stepName)
            ->setLabel($options['label'])
            ->setTemplate($this->getOption($options, 'template', null))
            ->setOrder($this->getOption($options, 'order', 0))
            ->setIsFinal($this->getOption($options, 'is_final', false))
            ->setEntity($this->getStepEntity($workflowDefinition, $stepName))
            ->setAllowedTransitions($this->getOption($options, 'allowed_transitions', array()));

        return $step;
    }

    /**
     * @param Attribute[]|Collection $attributes
     * @return array
     */
    protected function setAttributes($attributes)
    {
        $this->attributes = array();
        if ($attributes) {
            foreach ($attributes as $attribute) {
                $this->attributes[$attribute->getName()] = $attribute;
            }
        }
    }

    /**
     * @param array $attributeNames
     * @param string $stepName
     * @throws UnknownAttributeException
     */
    protected function assertAttributesExist(array $attributeNames, $stepName)
    {
        foreach ($attributeNames as $attributeName) {
            $this->assertAttributeExists($attributeName, $stepName);
        }
    }

    /**
     * @param string $attributeName
     * @param string $stepName
     * @throws UnknownAttributeException
     */
    protected function assertAttributeExists($attributeName, $stepName)
    {
        if (!isset($this->attributes[$attributeName])) {
            throw new UnknownAttributeException(
                sprintf('Unknown attribute "%s" at step "%s"', $attributeName, $stepName)
            );
        }
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param string $stepName
     * @return WorkflowStep|null
     * @throws WorkflowException
     */
    protected function getStepEntity(WorkflowDefinition $workflowDefinition, $stepName)
    {
        $workflowName = $workflowDefinition->getName();

        if (!array_key_exists($workflowName, $this->stepEntities)) {
            $this->stepEntities[$workflowName] = array();

            /** @var EntityRepository $stepRepository */
            $stepRepository = $this->getEntityManager()->getRepository('OroWorkflowBundle:WorkflowStep');
            /** @var WorkflowStep[] $workflowSteps */
            $workflowSteps = $stepRepository->findBy(array('definition' => $workflowDefinition));
            foreach ($workflowSteps as $workflowStep) {
                $this->stepEntities[$workflowName][$workflowStep->getName()] = $workflowStep;
            }
        }

        // need to be sure that step entity exists but only for existing definition
        // in case of not existing definition steps are only creating, so we can return null
        if (empty($this->stepEntities[$workflowName][$stepName])) {
            if ($this->getEntityManager()->getUnitOfWork()->isInIdentityMap($workflowDefinition)) {
                throw new WorkflowException(
                    sprintf('Workflow "%s" does not have step entity "%s"', $workflowName, $stepName)
                );
            } else {
                return null;
            }
        }

        return $this->stepEntities[$workflowName][$stepName];
    }
}
