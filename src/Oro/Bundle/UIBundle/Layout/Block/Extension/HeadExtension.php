<?php

namespace Oro\Bundle\UIBundle\Layout\Block\Extension;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Layout\AbstractBlockTypeExtension;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

use Oro\Bundle\LayoutBundle\Layout\Block\Type\HeadType;
use Oro\Bundle\NavigationBundle\Provider\TitleProvider;
use Oro\Bundle\NavigationBundle\Provider\TitleTranslator;

/**
 * This extension is intended to:
 * 1) load the title configured in navigation.yml.
 * 2) add the "title_parameters" option to the HeadType.
 * 3) add the "cache" option to the HeadType.
 *  Allowed values are (currently only null and false are implemented by HTML renderer - see layout.html.twig):
 *  * null     - no any cache configuration is applied (default behaviour).
 *  * false    - the document caching is prohibited.
 *  * true     - the default configuration of the cache should be applied (it depends on the layout output type).
 *  * int      - The maximum amount of time, in seconds, that the document will be considered fresh.
 *  * datetime - The date and time after which the document should be considered expired.
 */
class HeadExtension extends AbstractBlockTypeExtension
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(['cache' => null, 'title_parameters' => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(BlockView $view, BlockInterface $block, array $options)
    {
        $view->vars['cache'] = $options['cache'];

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
        $view->vars['title_parameters'] = $options['title_parameters'];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return HeadType::NAME;
    }
}
