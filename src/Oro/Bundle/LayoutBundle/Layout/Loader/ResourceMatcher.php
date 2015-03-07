<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

class ResourceMatcher implements ContextAwareInterface
{
    /** @var array */
    protected $voters = [];

    /** @var array */
    protected $sorted;

    /**
     * For automatically injecting voter should be registered as DI service
     * with tag layout.resource_matcher.voter
     *
     * @param VoterInterface $voter
     * @param int            $priority
     */
    public function addVoter(VoterInterface $voter, $priority = 0)
    {
        $this->voters[$priority][] = $voter;
        $this->sorted              = null;
    }

    /**
     * @param array  $path
     * @param string $resource
     *
     * @return bool
     */
    public function match(array $path, $resource)
    {
        $result = false;

        foreach ($this->getVoters() as $voter) {
            $result = $voter->vote($path, $resource);
            if (null !== $result) {
                break;
            }
        }

        return (bool)$result;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context)
    {
        foreach ($this->getVoters() as $voter) {
            if ($voter instanceof ContextAwareInterface) {
                $voter->setContext($context);
            }
        }
    }

    /**
     * @return VoterInterface[]
     */
    protected function getVoters()
    {
        if (!$this->sorted) {
            krsort($this->voters);
            $this->sorted = !empty($this->voters) ? call_user_func_array('array_merge', $this->voters) : [];
        }

        return $this->sorted;
    }
}
