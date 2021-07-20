<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

class AttributeFamilyFormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function onEdit(BeforeListRenderEvent $event)
    {
        $template = $event->getEnvironment()->render(
            'OroEntityConfigBundle:AttributeFamily:familyField.html.twig',
            ['form' => $event->getFormView()]
        );

        if (!$template) {
            return;
        }

        $scrollData = $event->getScrollData();

        $blockLabel = $this->translator->trans('oro.entity_config.attribute_family.entity_label');
        $blockId    = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);

        $scrollData->addSubBlockData($blockId, $subBlockId, $template);
    }
}
