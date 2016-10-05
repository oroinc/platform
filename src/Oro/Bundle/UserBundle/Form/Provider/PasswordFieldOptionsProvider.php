<?php

namespace Oro\Bundle\UserBundle\Form\Provider;

use Oro\Bundle\UserBundle\Provider\PasswordComplexityConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\PasswordComplexity;

/**
 * Generate various options for password fields based on System configuration
 */
class PasswordFieldOptionsProvider
{
    /**
     * @var array Map of config keys and suggest password character subsets
     */
    public static $suggestPasswordRulesMap = [
        PasswordComplexityConfigProvider::CONFIG_UPPER_CASE => 'upper_case',
        PasswordComplexityConfigProvider::CONFIG_NUMBERS => 'numbers',
        PasswordComplexityConfigProvider::CONFIG_SPECIAL_CHARS => 'special_chars',
    ];

    /** @var PasswordComplexityConfigProvider */
    protected $passwordConfigProvider;

    /** @var PasswordTooltipProvider */
    protected $passwordTooltip;

    /**
     * @param PasswordComplexityConfigProvider $passwordConfigProvider
     * @param PasswordTooltipProvider $passwordTooltip
     */
    public function __construct(
        PasswordComplexityConfigProvider $passwordConfigProvider,
        PasswordTooltipProvider $passwordTooltip
    ) {
        $this->passwordConfigProvider = $passwordConfigProvider;
        $this->passwordTooltip = $passwordTooltip;
    }

    /**
     * Add defaults for missing password field options
     *
     * @param array $options
     *
     * @return array
     */
    public function getOptions($options = [])
    {
        if (!isset($options['hint'])) {
            $options['hint'] = $this->getHintOption();
        }

        if (!isset($options['attr']['data-validation'])) {
            $options['attr']['data-validation'] = $this->getDataValidationOption();
        }

        return $options;
    }

    /**
     * Generate hint string
     *
     * @return string
     */
    public function getHintOption()
    {
        return $this->passwordTooltip->getTooltip();
    }

    /**
     * Generate data-validation string
     *
     * @return string
     */
    public function getDataValidationOption()
    {
        $dataValidation = [
            PasswordComplexity::class => new PasswordComplexity(
                [
                    'requireMinLength' => $this->passwordConfigProvider->getMinLength(),
                    'requireNumbers' => $this->passwordConfigProvider->getNumbers(),
                    'requireUpperCase' => $this->passwordConfigProvider->getUpperCase(),
                    'requireSpecialCharacter' => $this->passwordConfigProvider->getSpecialChars(),
                ]
            ),
        ];

        return json_encode($dataValidation);
    }

    /**
     * Generate config options required by Suggest password
     *
     * @return array
     */
    public function getSuggestPasswordOptions()
    {
        $enabledRules = $this->passwordConfigProvider->getAllRules();

        $rules = [];
        foreach (self::$suggestPasswordRulesMap as $configKey => $rule) {
            if ($enabledRules[$configKey]) {
                $rules[] = $rule;
            }
        }

        return [
            'data-require-length' => $this->passwordConfigProvider->getMinLength(),
            'data-require-rules' => join(',', $rules),
        ];
    }
}
