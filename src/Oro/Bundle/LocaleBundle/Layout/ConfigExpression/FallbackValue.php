<?php

namespace Oro\Bundle\LocaleBundle\Layout\ConfigExpression;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;

use Oro\Component\ConfigExpression\Func\GetValue;

class FallbackValue extends GetValue
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
    public function getName()
    {
        return 'fallback_value';
    }

    /**
     * {@inheritdoc}
     */
    protected function doEvaluate($context)
    {
        return $this->localizationHelper->getLocalizedValue(
            parent::doEvaluate($context)
        );
    }
}
