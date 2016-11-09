<?php

namespace Oro\Bundle\ScopeBundle\Manager;

interface ScopeCriteriaProviderInterface
{
    /**
     * @param array|object $context
     * @return array
     */
    public function getCriteriaByContext($context);

    /**
     * @return array
     */
    public function getCriteriaForCurrentScope();

    /**
     * @return string
     */
    public function getCriteriaField();
}
