<?php

namespace Oro\Bundle\CalendarBundle\Twig;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceExtension extends \Twig_Extension
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var Recurrence */
    protected $model;

    /**
     * RecurrenceExtension constructor.
     *
     * @param TranslatorInterface $translator
     * @param Recurrence $model
     */
    public function __construct(
        TranslatorInterface $translator,
        Recurrence $model
    ) {
        $this->translator = $translator;
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            'get_recurrence_text_value' => new \Twig_Function_Method(
                $this,
                'getRecurrenceTextValue'
            ),
        ];
    }

    /**
     * @param null|Entity\Recurrence $recurrence
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getRecurrenceTextValue(Entity\Recurrence $recurrence = null)
    {
        $textValue = $this->translator->trans('oro.calendar.calendarevent.recurrence.na');
        if ($recurrence) {
            $textValue = $this->model->getTextValue($recurrence);
        }

        return $textValue;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_recurrence';
    }
}
