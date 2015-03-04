<?php

namespace Oro\Bundle\UIBundle\Layout\Extension;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\NavigationBundle\Provider\TitleProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;

class HeadBlockTypeExtension extends AbstractBlockTypeExtension
{
    /** @var TitleProvider */
    protected $titleProvider;

    /** @var TitleTranslator */
    protected $titleTranslator;

    /**
     * @param TitleProvider   $titleProvider
     * @param TitleTranslator $titleTranslator
     */
    public function __construct(TitleProvider $titleProvider, TitleTranslator $titleTranslator)
    {
        $this->titleProvider   = $titleProvider;
        $this->titleTranslator = $titleTranslator;
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $title = $options['title'];
        if (!$title) {
            $routeName = $block->getContext()->getOr('route_name');
            if ($routeName) {
                $templates = $this->titleProvider->getTitleTemplates($routeName);
                if (isset($templates['title'])) {
                    $view->vars['title'] = $this->titleTranslator->trans($templates['title']);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'head';
    }
}
