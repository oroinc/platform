<?php

namespace Oro\Bundle\UserBundle\Provider;

use Oro\Bundle\ConfigBundle\Provider\AbstractProvider;

/**
 * Provides configuration of a system configuration form on the user level.
 */
class UserConfigurationFormProvider extends AbstractProvider
{
    private ?string $parentCheckboxLabel = null;

    public function setParentCheckboxLabel(string $label): void
    {
        $this->parentCheckboxLabel = $label;
    }

    #[\Override]
    protected function getTreeName(): string
    {
        return 'user_configuration';
    }

    #[\Override]
    protected function getParentCheckboxLabel(): string
    {
        return $this->parentCheckboxLabel ?? 'oro.user.user_configuration.use_default';
    }
}
