<?php

namespace Oro\Bundle\LocaleBundle\Layout;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionLanguageProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(LocalizationHelper $localizationHelper)
    {
        $this->localizationHelper = $localizationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new ExpressionFunction('localized_value', function () {
                return '$this->localizationHelper->getLocalizedValue($value)';
            }, function (array $variables, $value) {
                return $this->localizationHelper->getLocalizedValue($value);
            })
        ];
    }
}
