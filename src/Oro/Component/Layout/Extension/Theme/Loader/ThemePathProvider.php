<?php

namespace Oro\Bundle\LayoutBundle\Layout\Loader;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\ContextAwareInterface;

use Oro\Bundle\LayoutBundle\Model\Theme;
use Oro\Bundle\LayoutBundle\Theme\ThemeManager;

class ThemePathProvider implements PathProviderInterface, ContextAwareInterface
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
    public function getPaths(array $existingPaths)
    {
        $themeName = $this->context->getOr('theme');
        if ($themeName) {
            $themePaths = [];
            foreach ($this->getThemesHierarchy($themeName) as $theme) {
                $existingPaths[] = $themePaths[] = $theme->getDirectory();
            }

            $actionName = $this->context->getOr('action');
            if ($actionName) {
                foreach ($themePaths as $path) {
                    $existingPaths[] = implode(self::DELIMITER, [$path, $actionName]);
                }
            }

            $routeName = $this->context->getOr('route_name');
            if ($routeName) {
                foreach ($themePaths as $path) {
                    $existingPaths[] = implode(self::DELIMITER, [$path, $routeName]);
                }
            }
        }

        return $existingPaths;
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
            $theme = $this->themeManager->getTheme($themeName);

            $hierarchy[] = $theme;
            $themeName   = $theme->getParentTheme();
        }

        return array_reverse($hierarchy);
    }
}
