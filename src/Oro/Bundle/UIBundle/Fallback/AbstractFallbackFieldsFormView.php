<?php

namespace Oro\Bundle\UIBundle\Fallback;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

abstract class AbstractFallbackFieldsFormView
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->doctrineHelper = $doctrineHelper;
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param $templateName
     * @param $entity
     * @param int $blockId
     * @param int $subBlockId
     */
    protected function addBlockToEntityView(
        BeforeListRenderEvent
        $event, $templateName,
        $entity,
        $blockId = 0,
        $subBlockId = 0
    )
    {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['entity' => $entity]
        );

        $event->getScrollData()->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param $templateName
     * @param null $sectionTitle
     * @param int $blockId
     * @param int $subBlockId
     */
    protected function addBlockToEntityEdit(
        BeforeListRenderEvent $event,
        $templateName,
        $sectionTitle = null,
        $blockId = 0,
        $subBlockId = 0
    )
    {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['form' => $event->getFormView()]
        );

        $scrollData = $event->getScrollData();
        if ($sectionTitle === null) {
            $scrollData->addSubBlockData($blockId, $subBlockId, $template);

            return;
        }

        $data = $scrollData->getData();
        $expectedLabel = $this->translator->trans($sectionTitle);
        foreach ($data['dataBlocks'] as $blockId => $blockData) {
            if ($blockData['title'] == $expectedLabel) {
                $scrollData->addSubBlockData($blockId, 0, $template);

                return;
            }
        }
    }

    /**
     * @param $entityPath
     * @return null|object
     */
    protected function getEntityFromRequest($entityPath)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $entityId = (int)$request->get('id');
        if (!$entityId) {
            return null;
        }

        return $this->doctrineHelper->getEntityReference($entityPath, $entityId);
    }
}
