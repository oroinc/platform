<?php

namespace Oro\Component\ChainProcessor;

/**
 * The provider that can be used if the ProcessorBag configuration is already builded.
 * For example it might be used in case when the configuration is build by DIC compiler pass.
 */
class ProcessorBagConfigProvider implements ProcessorBagConfigProviderInterface
{
    /** @var array [action => [group, ...], ...] */
    private $groups;

    /** @var array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...] */
    private $processors;

    /**
     * @param array $groups     [action => [group, ...], ...]
     * @param array $processors [action => [[processor id, [attribute name => attribute value, ...]], ...], ...]
     */
    public function __construct(array $groups, array $processors)
    {
        $this->groups = $groups;
        $this->processors = $processors;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors()
    {
        return $this->processors;
    }
}
