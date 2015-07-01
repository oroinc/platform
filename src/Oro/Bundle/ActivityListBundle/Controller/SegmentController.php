<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;

/**
 * @Route("/activity-list/segment")
 */
class SegmentController extends Controller
{
    /**
     * @Route("/activity-condition")
     * @Template
     */
    public function activityConditionAction(Request $request)
    {
        $params = $request->attributes->get('params', []);
        $conditionOptions = [
            'activityConditionOptions' => [
                'listOption'     => $this->forward(
                    'OroActivityListBundle:Api/Rest/ActivityList:getActivityListOption',
                    [],
                    ['_format' => 'json']
                )->getContent(),
                'entitySelector' => sprintf('[data-ftid=%s]', $params['entity_choice_id']),
                'fieldsLoaderSelector' =>  sprintf(
                    '[data-ftid=%soro_api_querydesigner_fields_entity]',
                    $params['entity_choice_id']
                ),
                'fieldChoice' => [
                    'select2' => [
                        'placeholder' => $this->getTranslator()->trans(
                            'oro.query_designer.condition_builder.choose_entity_field'
                        ),
                    ],
                ],
                'extensions' => [],
            ]
        ];

        $dispatcher = $this->getEventDispatcher();
        if (!$dispatcher->hasListeners(ActivityConditionOptionsLoadEvent::EVENT_NAME)) {
            return $conditionOptions;
        }

        $event = new ActivityConditionOptionsLoadEvent($conditionOptions['activityConditionOptions']);
        $dispatcher->dispatch(ActivityConditionOptionsLoadEvent::EVENT_NAME, $event);

        return [
            'activityConditionOptions' => $event->getOptions(),
        ];
    }

    /**
     * @return EventDispatcherInterface
     */
    protected function getEventDispatcher()
    {
        return $this->get('event_dispatcher');
    }

    /**
     * @return TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->get('translator');
    }
}
