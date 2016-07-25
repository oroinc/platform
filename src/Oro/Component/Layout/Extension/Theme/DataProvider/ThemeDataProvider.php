<?php

namespace Oro\Component\Layout\Extension\Theme\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

class ThemeDataProvider
{
    /** @var ThemeManager */
    protected $themeManager;

    /**
     * @param ThemeManager $themeManager
     */
    public function __construct(ThemeManager $themeManager)
    {
        $this->themeManager = $themeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        return $this->themeManager->getTheme($context->get('theme'));
    }
}
