<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowGroup;

use Oro\Component\Action\Model\AbstractAssembler as BaseAbstractAssembler;

class GroupAssembler extends BaseAbstractAssembler
{
    /** @var ObjectRepository */
    protected $repository;

    /**
     * @param ObjectRepository $repository
     */
    public function __construct(ObjectRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param array $configuration
     * @param Attribute[]|Collection $attributes
     * @return ArrayCollection
     */
    public function assemble(array $configuration)
    {
        $groups = new ArrayCollection();
        $this->processGroups(
            $groups,
            WorkflowGroup::TYPE_EXCLUSIVE_ACTIVE,
            $this->getOption($configuration, WorkflowConfiguration::NODE_EXCLUSIVE_ACTIVE_GROUPS, [])
        );
        $this->processGroups(
            $groups,
            WorkflowGroup::TYPE_EXCLUSIVE_RECORD,
            $this->getOption($configuration, WorkflowConfiguration::NODE_EXCLUSIVE_RECORD_GROUPS, [])
        );

        return $groups;
    }


    /**
     * @param Collection $groups
     * @param int $type
     * @param array $groupNames
     */
    private function processGroups(Collection $groups, $type, array $groupNames)
    {
        foreach ($groupNames as $groupName) {
            $group = $this->repository->findOneBy(
                [
                    'type' => $type,
                    'name' => $groupName,
                ]
            );
            if (!$group) {
                $group = new WorkflowGroup();
                $group
                    ->setType($type)
                    ->setName($groupName);
            }
            if(!$groups->contains($group)) {
                $groups->add($group);
            }
        }
    }
}
