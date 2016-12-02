<?php

namespace Oro\Bundle\UIBundle\Fallback;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;

use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;

abstract class AbstractFallbackFieldsFormView
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param RequestStack $requestStack
     * @param ManagerRegistry $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        RequestStack $requestStack,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator
    ) {
        $this->requestStack = $requestStack;
        $this->doctrine = $doctrine;
        $this->translator = $translator;
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $templateName
     * @param object $entity
     * @param int $blockId
     * @param int $subBlockId
     */
    public function addBlockToEntityView(
        BeforeListRenderEvent
        $event,
        $templateName,
        $entity,
        $blockId = 0,
        $subBlockId = 0
    ) {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['entity' => $entity]
        );

        $event->getScrollData()->addSubBlockData($blockId, $subBlockId, $template);
    }

    /**
     * @param BeforeListRenderEvent $event
     * @param string $templateName
     * @param null $sectionTitle
     * @param int $blockId
     * @param int $subBlockId
     */
    public function addBlockToEntityEdit(
        BeforeListRenderEvent $event,
        $templateName,
        $sectionTitle = null,
        $blockId = 0,
        $subBlockId = 0
    ) {
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
                $scrollData->addSubBlockData($blockId, $subBlockId, $template);

                return;
            }
        }
    }

    /**
     * @param string $entityPath
     * @return null|object
     */
    public function getEntityFromRequest($entityPath)
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request) {
            return null;
        }

        $entityId = (int)$request->get('id');
        if (!$entityId) {
            return null;
        }

        /** @var EntityManager $em */
        $em = $this->doctrine->getManagerForClass($entityPath);
        return $em->getReference($entityPath, $entityId);
    }
}
