<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

use Oro\Bundle\LayoutBundle\Model\Theme;
use Oro\Bundle\LayoutBundle\Theme\ThemeManager;

abstract class AbstractPathProvider implements PathProviderInterface, ContextAwareInterface
{
    /** @var array */
    protected $filterPaths = [];

    /** @var ThemeManager */
    protected $manager;

    /** @var ContextInterface */
    protected $context;

    /**
     * @param ThemeManager $manager
     */
    public function __construct(ThemeManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(ContextInterface $context)
    {
        $this->context = $context;
    }

    /**
     * Returns theme inheritance hierarchy with root theme as first item
     *
     * @param string $themeName
     *
     * @return Theme[]
     */
    protected function getThemesHierarchy($themeName)
    {
        $hierarchy = [];

        while (null !== $themeName) {
            $theme = $this->manager->getTheme($themeName);

            $hierarchy[] = $theme;
            $themeName   = $theme->getParentTheme();
        }

        return array_reverse($hierarchy);
    }
}
