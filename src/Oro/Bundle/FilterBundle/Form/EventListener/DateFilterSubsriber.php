<?php

namespace Oro\Bundle\FilterBundle\Form\EventListener;

use Carbon\Carbon;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\FilterBundle\Expression\Date\Compiler;

class DateFilterSubsriber implements EventSubscriberInterface
{
    /** @var Compiler */
    protected $expressionCompiler;

    /** @var LocaleSettings */
    protected $localeSettings;

    public function __construct(Compiler $compiler, LocaleSettings $localeSettings)
    {
        $this->expressionCompiler = $compiler;
        $this->localeSettings     = $localeSettings;
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

        if ($start instanceof \DateTime) {
            $start->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }
        if ($end instanceof \DateTime) {
            $end->setTimezone(new \DateTimeZone($this->localeSettings->getTimeZone()));
        }

        $data['start'] = (string)$start;
        $data['end']   = (string)$end;

        $event->setData($data);
    }
}
