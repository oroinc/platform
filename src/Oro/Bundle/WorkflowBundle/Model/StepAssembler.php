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
     * @var FormOptionsAssembler
     */
    protected $formOptionsAssembler;

    /**
     * @param ContainerInterface $container
     * @param FormOptionsAssembler $formOptionsAssembler
     */
    public function __construct(ContainerInterface $container, FormOptionsAssembler $formOptionsAssembler)
    {
        $this->container = $container;
        $this->formOptionsAssembler = $formOptionsAssembler;
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
            ->setAllowedTransitions($this->getOption($options, 'allowed_transitions', array()))
            ->setEntity($this->getStepEntity($workflowDefinition, $stepName))
            ->setFormType($this->getOption($options, 'form_type', WorkflowStepType::NAME))
            ->setFormOptions($this->assembleFormOptions($options, $stepName))
            ->setViewAttributes($this->assembleViewAttributes($options, $stepName));

        return $step;
    }

    /**
     * @param array $options
     * @param string $stepName
     * @return array
     */
    protected function assembleFormOptions(array $options, $stepName)
    {
        $formOptions = $this->getOption($options, 'form_options', array());
        return $this->formOptionsAssembler->assemble($formOptions, $this->attributes, 'step', $stepName);
    }

    /**
     * @param array $options
     * @param string $stepName
     * @return array
     * @throws InvalidParameterException
     */
    protected function assembleViewAttributes(array $options, $stepName)
    {
        $viewAttributes = $this->getOption($options, 'view_attributes', array());

        if (!is_array($viewAttributes)) {
            throw new InvalidParameterException(
                sprintf('Option "view_attributes" at step "%s" must be an array', $stepName)
            );
        }

        $result = array();
        foreach ($viewAttributes as $index => $viewAttribute) {
            if (isset($viewAttribute['attribute'])) {
                $attributeName = $viewAttribute['attribute'];
                $this->assertAttributeExists($attributeName, $stepName);
                if (!isset($viewAttribute['path'])) {
                    $viewAttribute['path'] = '$' . $viewAttribute['attribute'];
                }
                if (!isset($viewAttribute['label'])) {
                    $viewAttribute['label'] = $this->attributes[$viewAttribute['attribute']]->getLabel();
                }
            } elseif (!isset($viewAttribute['path'])) {
                throw new InvalidParameterException(
                    sprintf(
                        'Option "path" or "attribute" at view attribute "%s" of step "%s" is required',
                        $index,
                        $stepName
                    )
                );
            } elseif (!isset($viewAttribute['label'])) {
                throw new InvalidParameterException(
                    sprintf(
                        'Option "label" at view attribute "%s" of step "%s" is required',
                        $index,
                        $stepName
                    )
                );
            }
            $result[] = $this->passConfiguration($viewAttribute);
        }
        return $result;
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
