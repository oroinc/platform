<?php

namespace Oro\Bundle\ActionBundle\Helper;

interface SubstitutionVisitorInterface
{
    /**
     * @param mixed $target the value of target
     * @param mixed $replacement the value of replacement
     * @param string $targetKey the key of target in substitution map
     * @param string $replacementKey the key of replacement in substitution map
     * @return null|void
     */
    public function visit($target, $replacement, $targetKey, $replacementKey);
}
