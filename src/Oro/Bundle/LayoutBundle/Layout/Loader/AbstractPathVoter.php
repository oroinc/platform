<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

abstract class AbstractPathVoter implements VoterInterface, ContextAwareInterface
{
    /** @var array */
    protected $filterPaths;

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context)
    {
        $this->filterPaths = $this->getFilterPath($context);
    }

    /**
     * {@inheritdoc}
     */
    public function vote(array $path, $resource)
    {
        foreach ($this->filterPaths as $currentPath) {
            if (count($path) <= count($currentPath)) {
                $equals = true;
                foreach ($path as $k => $v) {
                    if ($v !== $currentPath[$k]) {
                        $equals = false;

                        break;
                    }
                }

                if ($equals) {
                    return true;
                }
            }
        }

        return null;
    }

    /**
     * @param ContextInterface $context
     *
     * @return array
     */
    abstract protected function getFilterPath(ContextInterface $context);
}
