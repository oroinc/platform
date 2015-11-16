<?php

namespace Oro\Bundle\LayoutBundle\Assetic;

use Assetic\Factory\Resource\ResourceInterface;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\StylesheetsType;
use Oro\Component\Layout\Layout;
use Oro\Component\Layout\BlockView;
use Oro\Component\Layout\LayoutManager;
use Oro\Component\Layout\LayoutContext;
use Oro\Component\Layout\Extension\Theme\Model\ThemeManager;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

class LayoutResource implements ResourceInterface
{
    /** @var ThemeManager */
    protected $themeManager;

    /** @var LayoutManager $layoutManager */
    protected $layoutManager;

    public function __construct(ThemeManager $themeManager, LayoutManager $layoutManager)
    {
        $this->themeManager = $themeManager;
        $this->layoutManager = $layoutManager;
    }

    public function isFresh($timestamp)
    {
        return true;
    }

    public function getContent()
    {
        $formulae = [];
        $themes = $this->themeManager->getThemeNames();
        foreach ($themes as $theme) {
            $layout = $this->getLayout($theme);
            if ($layout) {
                $formulae += $this->collectViewStylesheets($theme, $layout->getView());
            }
        }
        return $formulae;
    }

    /**
     * @param string $theme
     * @return Layout
     */
    protected function getLayout($theme)
    {
        $builder = $this->layoutManager->getLayoutBuilder();
        $builder->add('root', null, 'root');

        $context = new LayoutContext();
        $context->set('theme', $theme);

        try {
            $layout = $builder->getLayout($context);
        } catch (NoSuchPropertyException $ex) {
            $ex;
            $layout = null;
        }

        return $layout;
    }

    protected function collectViewStylesheets($theme, BlockView $view, $formulae = [])
    {
        if ($view->vars['block_type'] === StylesheetsType::NAME) {
            $name = 'layout_' . $theme . $view->vars['cache_key'];
            $formulae[$name] = [
                $view->vars['inputs'],
                $view->vars['filters'],
                [
                    'output' => $view->vars['output'],
                    'name' => $name,
                ],
            ];
        } else {
            foreach ($view as $childView) {
                $formulae = $this->collectViewStylesheets($theme, $childView, $formulae);
            }
        }
        return $formulae;
    }

    public function __toString()
    {
        return 'layout';
    }
}
