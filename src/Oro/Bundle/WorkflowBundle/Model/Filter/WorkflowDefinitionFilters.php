<?php

namespace Oro\Bundle\WorkflowBundle\Model\Filter;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class WorkflowDefinitionFilters
{
    const TYPE_DEFAULT = '';
    const TYPE_SYSTEM = 'system';

    /** @var ArrayCollection|WorkflowDefinitionFilterInterface[] */
    protected $filters;

    /** @var RequestStack */
    protected $requestStack;

    /** @var string */
    protected $type = self::TYPE_DEFAULT;

    /** @var bool */
    protected static $enabled = true;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;

        $this->filters = new ArrayCollection();
    }

    /**
     * @param WorkflowDefinitionFilterInterface $filter
     */
    public function addFilter(WorkflowDefinitionFilterInterface $filter)
    {
        if (!$this->filters->contains($filter)) {
            $this->filters->add($filter);
        }
    }

    /**
     * @return ArrayCollection|WorkflowDefinitionFilterInterface[]
     */
    public function getFilters()
    {
        if (!$this->isEnabled()) {
            return new ArrayCollection();
        }

        return  $this->filters->filter(function (WorkflowDefinitionFilterInterface $filter) {
            return !$this->isSystem() || $filter instanceof SystemFilterInterface;
        });
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return static::$enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        static::$enabled = (bool)$enabled;
    }

    /**
     * @return bool
     */
    protected function isSystem()
    {
        return $this->type === self::TYPE_SYSTEM || null === $this->requestStack->getMasterRequest();
    }
}
