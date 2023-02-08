<?php

namespace Oro\Component\Layout\Extension\Theme\PathProvider;

use Oro\Component\Layout\ContextAwareInterface;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\Extension\Theme\Model\Theme;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;

/**
 * Determines theme path based on context data.
 */
class ThemePathProvider implements PathProviderInterface, ContextAwareInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var ContextInterface */
    protected $context;

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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getPaths(array $existingPaths)
    {
        $themeName = $this->context->getOr('theme');
        if ($themeName) {
            $themePaths = [];

            $themes = $this->themeManager->getThemesHierarchy($themeName);
            foreach ($themes as $theme) {
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

            $pageTemplate = $this->context->getOr('page_template');
            if ($pageTemplate) {
                $theme = end($themes);
                if ($theme->getPageTemplate($this->context->getOr('page_template'), $routeName)) {
                    foreach ($themePaths as $path) {
                        $existingPaths[] =
                            implode(self::DELIMITER, [$path, $routeName, 'page_template', $pageTemplate]);
                    }
                }
            }
        }

        return $existingPaths;
    }
}
