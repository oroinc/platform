<?php

namespace Oro\Bundle\FilterBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\FilterBundle\Expression\Date\Compiler;

class DateFilterSubscriber implements EventSubscriberInterface
{
    /** @var Compiler */
    protected $expressionCompiler;

    public function __construct(Compiler $compiler)
    {
        $this->expressionCompiler = $compiler;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'processParams',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function processParams(FormEvent $event)
    {
        $data = $event->getData();

        $start = $this->expressionCompiler->compile($data['start']);
        $end   = $this->expressionCompiler->compile($data['end']);

        $data['start'] = (string)$start;
        $data['end']   = (string)$end;

        $event->setData($data);
    }
}
