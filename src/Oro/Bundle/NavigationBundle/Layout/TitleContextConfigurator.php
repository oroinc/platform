<?php

namespace Oro\Bundle\NavigationBundle\Layout;

use Symfony\Component\OptionsResolver\Options;

use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

use Oro\Bundle\NavigationBundle\Provider\TitleProvider;

class TitleContextConfigurator implements ContextConfiguratorInterface
{
    /** @var TitleProvider */
    protected $titleProvider;

    /**
     * @param TitleProvider $titleProvider
     */
    public function __construct(TitleProvider $titleProvider)
    {
        $this->titleProvider = $titleProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function configureContext(ContextInterface $context)
    {
        $context->getDataResolver()
            ->setDefaults(['title_template' => ''])
            ->setNormalizers(
                [
                    'title_template' => function (Options $options, $titleTemplate) {
                        if (!$titleTemplate && isset($options['route_name'])) {
                            $templates = $this->titleProvider->getTitleTemplates($options['route_name']);
                            if (isset($templates['title'])) {
                                $titleTemplate = $templates['title'];
                            }
                        }

                        return $titleTemplate;
                    }
                ]
            );
    }
}
