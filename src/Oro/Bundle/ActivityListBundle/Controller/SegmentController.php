<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

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
                'listOptions'     => json_decode($this->forward(
                    'OroActivityListBundle:Api/Rest/ActivityList:getActivityListOption',
                    [],
                    ['_format' => 'json']
                )->getContent()),
                'fieldChoice' => [
                    'select2' => [
                        'placeholder' => $this->getTranslator()->trans(
                            'oro.query_designer.condition_builder.choose_entity_field'
                        ),
                    ],
                ],
            ],
            'params' => $params,
        ];

        $dispatcher = $this->getEventDispatcher();
        if (!$dispatcher->hasListeners(ActivityConditionOptionsLoadEvent::EVENT_NAME)) {
            return $conditionOptions;
        }

        $event = new ActivityConditionOptionsLoadEvent($conditionOptions['activityConditionOptions']);
        $dispatcher->dispatch(ActivityConditionOptionsLoadEvent::EVENT_NAME, $event);

        return [
            'activityConditionOptions' => $event->getOptions(),
            'params' => $params,
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
