<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\Twig\Parser\PlaceholderTokenParser;
use Oro\Bundle\UIBundle\View\ScrollData;

class UiExtension extends \Twig_Extension
{
    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritDoc}
     */
    public function getTokenParsers()
    {
        return array(
            new PlaceholderTokenParser()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_ui_scroll_data_before',
                [$this, 'scrollDataBefore'],
                ['needs_environment' => true]
            )
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param $pageIdentifier
     * @param array $data
     * @param FormView $formView
     * @return array
     */
    public function scrollDataBefore(
        \Twig_Environment $environment,
        $pageIdentifier,
        array $data,
        FormView $formView = null
    ) {
        $event = new BeforeListRenderEvent($environment, new ScrollData($data), $formView);
        $this->eventDispatcher->dispatch('oro_ui.scroll_data.before.' . $pageIdentifier, $event);

        return $event->getScrollData()->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_ui';
    }
}
