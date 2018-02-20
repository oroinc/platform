<?php

namespace Oro\Bundle\ReportBundle\Form\EventListener;

use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Form\Type\ReportType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DateGroupingFormSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::POST_SET_DATA => 'onPostSetData',
            FormEvents::SUBMIT => 'onSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSetData(FormEvent $event)
    {
        /** @var Report $data */
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof Report || !$form->has(ReportType::DATE_GROUPING_FORM_NAME)) {
            return;
        }
        /** @var  $dateGroupingModel */
        $dateGroupingModel = $form->get(ReportType::DATE_GROUPING_FORM_NAME)->getData();
        if (!$dateGroupingModel instanceof DateGrouping) {
            $dateGroupingModel = new DateGrouping();
        }
        $definition = json_decode($data->getDefinition(), true);
        if (!is_array($definition) || !array_key_exists(DateGroupingType::DATE_GROUPING_NAME, $definition)) {
            $dateGroupingModel->setUseDateGroupFilter(false);
        } else {
            $dateGroupingArray = $definition[DateGroupingType::DATE_GROUPING_NAME];
            if (array_key_exists(DateGroupingType::FIELD_NAME_ID, $dateGroupingArray)) {
                $dateGroupingModel->setFieldName($dateGroupingArray[DateGroupingType::FIELD_NAME_ID]);
            }

            if (array_key_exists(DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID, $dateGroupingArray)) {
                $dateGroupingModel->setUseSkipEmptyPeriodsFilter(
                    $dateGroupingArray[DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID]
                );
            }
            $dateGroupingModel->setUseDateGroupFilter(true);
        }

        $form->get(ReportType::DATE_GROUPING_FORM_NAME)->setData($dateGroupingModel);
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof Report || !$form->has(ReportType::DATE_GROUPING_FORM_NAME)) {
            return;
        }

        /** @var DateGrouping $dateGroupingModel */
        $dateGroupingModel = $form->get(ReportType::DATE_GROUPING_FORM_NAME)->getData();
        $definition = json_decode($data->getDefinition(), true);
        if (!is_array($definition)) {
            $definition = [];
        }

        if (false === $dateGroupingModel->getUseDateGroupFilter()) {
            unset($definition[DateGroupingType::DATE_GROUPING_NAME]);
        } else {
            if (!array_key_exists(DateGroupingType::DATE_GROUPING_NAME, $definition)) {
                $definition[DateGroupingType::DATE_GROUPING_NAME] = [];
            }

            $definition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::FIELD_NAME_ID] =
                $dateGroupingModel->getFieldName();
            $definition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::USE_SKIP_EMPTY_PERIODS_FILTER_ID] =
                $dateGroupingModel->getUseSkipEmptyPeriodsFilter();
            $definition[DateGroupingType::DATE_GROUPING_NAME][DateGroupingType::USE_DATE_GROUPING_FILTER] =
                $dateGroupingModel->getUseDateGroupFilter();
        }

        $data->setDefinition(json_encode($definition));
    }
}
