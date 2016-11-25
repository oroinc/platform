<?php

namespace Oro\Bundle\WorkflowBundle\Command\Upgrade20;

interface ResourceTranslationGenerator
{
    /**
     * @param ConfigResource $configResource
     * @return \Traversable|GeneratedTranslationResource[]
     */
    public function generate(ConfigResource $configResource);
}
