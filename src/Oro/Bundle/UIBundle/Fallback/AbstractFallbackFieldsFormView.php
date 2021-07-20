<?php

namespace Oro\Bundle\UIBundle\Fallback;

use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The base listener which should be extended by class which will manipulate scroll data blocks.
 */
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
     * @param string|null $sectionTitle
     * @param int $blockId
     * @param int $subBlockId
     * @return int|string|void
     */
    public function addBlockToEntityView(
        BeforeListRenderEvent $event,
        $templateName,
        $entity,
        $sectionTitle = null,
        $blockId = 0,
        $subBlockId = 0
    ) {
        $template = $event->getEnvironment()->render(
            $templateName,
            ['entity' => $entity]
        );

        $this->addBlockWithSectionTitle($event->getScrollData(), $sectionTitle, $blockId, $subBlockId, $template);
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

        $this->addBlockWithSectionTitle($event->getScrollData(), $sectionTitle, $blockId, $subBlockId, $template);
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

    protected function addBlockWithSectionTitle(
        ScrollData $scrollData,
        $sectionTitle,
        $blockId,
        $subBlockId,
        $template
    ) {
        if ($sectionTitle === null) {
            $scrollData->addSubBlockData($blockId, $subBlockId, $template);

            return;
        }

        $data = $scrollData->getData();
        $expectedLabel = $this->translator->trans($sectionTitle);
        foreach ($data[ScrollData::DATA_BLOCKS] as $searchBlockId => $blockData) {
            if ($blockData[ScrollData::TITLE] === $expectedLabel) {
                $scrollData->addSubBlockData($searchBlockId, $subBlockId, $template);

                return;
            }
        }
    }
}
