<?php

namespace Oro\Bundle\ActivityListBundle\Controller;

use Oro\Bundle\ActivityListBundle\Controller\Api\Rest\ActivityListController as RestActivityListController;
use Oro\Bundle\ActivityListBundle\Event\ActivityConditionOptionsLoadEvent;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide functionality to manage activity conditions
 *
 * @Route("/activity-list/segment")
 */
class SegmentController extends AbstractController
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
                'listOptions' => json_decode(
                    $this->forward(
                        RestActivityListController::class . '::getActivityListOptionAction',
                        [],
                        ['_format' => 'json']
                    )->getContent()
                ),
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
        $dispatcher->dispatch($event, ActivityConditionOptionsLoadEvent::EVENT_NAME);

        return [
            'activityConditionOptions' => $event->getOptions(),
            'params' => $params,
        ];
    }

    protected function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->get(EventDispatcherInterface::class);
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                EventDispatcherInterface::class,
            ]
        );
    }
}
