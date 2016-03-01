<?php

namespace Oro\Bundle\UIBundle\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Twig_Environment;

use Oro\Bundle\UIBundle\Event\Events;
use Oro\Bundle\UIBundle\Event\BeforeViewRenderEvent;

class ViewExtension extends \Twig_Extension
{
    /** @var EventDispatcherInterface */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'oro_view_process' => new \Twig_Function_Method(
                $this,
                'process',
                [
                    'needs_environment' => true
                ]
            )
        ];
    }

    /**
     * @param \Twig_Environment $environment
     * @param array             $data
     * @param object            $entity
     *
     * @return array
     */
    public function process(Twig_Environment $environment, array $data, $entity)
    {
        $event = new BeforeViewRenderEvent($environment, $data, $entity);
        $this->eventDispatcher->dispatch(Events::BEFORE_VIEW_RENDER, $event);

        return $event->getData();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_view_process';
    }
}
