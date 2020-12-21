<?php

namespace Oro\Component\ChainProcessor;

/**
 * The provider that can be used if the ProcessorBag configuration is already built.
 * For example it might be used in case when the configuration is build by DIC compiler pass.
 */
class ProcessorBagConfigProvider implements ProcessorBagConfigProviderInterface
{
    /** @var array [action => [group, ...], ...] */
    private $groups;

    /** @var array [action => [[processor id, [attribute name => attribute value, ...]], ...], ...] */
    private $processors;

    /** @var string[]|null */
    private $actions;

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
    public function getActions(): array
    {
        if (null === $this->actions) {
            $this->actions = array_keys($this->processors);
        }

        return $this->actions;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups(string $action): array
    {
        return $this->groups[$action] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessors(string $action): array
    {
        return $this->processors[$action] ?? [];
    }
}
