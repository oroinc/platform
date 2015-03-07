<?php

namespace Oro\Bundle\LayoutBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use Oro\Bundle\LayoutBundle\Theme\ThemeManager;

class ThemeDataProvider implements DataProviderInterface, ContextAwareInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var ContextInterface */
    protected $context;

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
    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        // TODO: it is expected that REST API for layout themes will be created
        // and this method will return URL of this API
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->themeManager->getTheme($this->context->get('theme'));
    }
}
