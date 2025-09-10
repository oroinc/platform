<?php

namespace Oro\Bundle\FormBundle\Captcha;

use Oro\Bundle\ConfigBundle\Config\GlobalScopeManager;

/**
 * The registry to hold a list of CAPTCHA protected forms.
 */
class CaptchaProtectedFormsRegistry
{
    public const string ALL = 'all';
    public const string GLOBAL = 'global';

    private array $protectedForms = [];

    private array $restrictions = [
        self::GLOBAL => 0,
        self::ALL => 100
    ];

    private array $scopeToRestrictionsMapping = [
        GlobalScopeManager::SCOPE_NAME => self::GLOBAL
    ];

    public function __construct(iterable $protectedForms)
    {
        if ($protectedForms instanceof \Traversable) {
            $protectedForms = iterator_to_array($protectedForms);
        }

        $this->protectedForms = $protectedForms;
    }

    public function protectForm(string $name): void
    {
        $this->protectedForms[$name] = self::ALL;
    }

    public function protectFormWithRestrictions(
        string $name,
        string $restriction = self::ALL
    ): void {
        $this->protectedForms[$name] = $restriction;
    }

    /**
     * @return string[]
     */
    public function getProtectedForms(): array
    {
        return array_keys($this->protectedForms);
    }

    public function addScopeToRestrictionMapping(string $scope, string $scopeRestriction, int $level): void
    {
        $this->restrictions[$scopeRestriction] = $level;
        $this->scopeToRestrictionsMapping[$scope] = $scopeRestriction;
    }

    public function getProtectedFormsByScope(string $scope): array
    {
        $scopeRestriction = self::ALL;

        if (array_key_exists($scope, $this->scopeToRestrictionsMapping)) {
            $scopeRestriction = $this->scopeToRestrictionsMapping[$scope];
        }

        $scopeRestrictionLevel = $this->restrictions[$scopeRestriction];

        return array_filter(
            $this->getProtectedForms(),
            function (string $form) use ($scopeRestrictionLevel): bool {
                $formRestriction = $this->protectedForms[$form];
                $formRestrictionLevel = $this->restrictions[$formRestriction];

                return $formRestrictionLevel >= $scopeRestrictionLevel;
            }
        );
    }
}
