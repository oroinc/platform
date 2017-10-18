<?php

namespace Oro\Bundle\ApiBundle\Processor;

use Oro\Component\ChainProcessor\ChainApplicableChecker;
use Oro\Component\ChainProcessor\ProcessorApplicableCheckerFactoryInterface;

/**
 * Creates an applicable checker that should be used to check whether API processor should be executed or not.
 * This factory can be used only for actions that do not contain groups.
 */
class ProcessorApplicableCheckerWithoutGroupsFactory implements ProcessorApplicableCheckerFactoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function createApplicableChecker()
    {
        $applicableChecker = new ChainApplicableChecker();
        $applicableChecker->addChecker(
            new MatchApplicableChecker([], ['class', 'parentClass'])
        );

        return $applicableChecker;
    }
}
