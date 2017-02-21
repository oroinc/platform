<?php

namespace Oro\Bundle\QueryDesignerBundle\Form\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Oro\Bundle\QueryDesignerBundle\Form\Type\AbstractQueryDesignerType;
use Oro\Bundle\QueryDesignerBundle\Form\Type\DateGroupingType;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\Model\DateGrouping;

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
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof AbstractQueryDesigner || !$form->has(AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME)) {
            return;
        }
        $dateGroupingModel = $form->get(AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME)->getData();
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

        $form->get(AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME)->setData($dateGroupingModel);
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data instanceof AbstractQueryDesigner
            || !$form->has(AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME)
        ) {
            return;
        }

        $dateGroupingModel = $form->get(AbstractQueryDesignerType::DATE_GROUPING_FORM_NAME)->getData();
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
        }

        $data->setDefinition(json_encode($definition));
    }
}
