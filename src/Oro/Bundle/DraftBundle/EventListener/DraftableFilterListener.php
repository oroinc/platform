<?php

namespace Oro\Bundle\DraftBundle\EventListener;

use Oro\Bundle\DraftBundle\Doctrine\DraftableFilter;
use Oro\Bundle\DraftBundle\Entity\DraftableInterface;
use Oro\Bundle\DraftBundle\Helper\DraftHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

/**
 * Disable Draftable Filter for Draft actions based on kernel event
 */
class DraftableFilterListener
{
    private DoctrineHelper $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $request = $event->getRequest();
        $entityId = $request->get('entityId');
        $className = null;

        if ($entityId) {
            $id = is_array($entityId) && isset($entityId['id']) ? $entityId['id'] : $entityId;
            $className = $request->get('entityClass');
        } else {
            $id = $event->getRequest()->get('id');
            if ($id) {
                $className = $this->getClassName($event);
            }
        }

        $this->allowDraftAction($id, $className);
    }

    private function getClassName(ControllerEvent $event): ?string
    {
        $r = $this->getReflectionFunctionByController($event->getController());
        $parameters = $r->getParameters();
        if (isset($parameters[0])) {
            $idParam = $parameters[0];
            if ($idParam) {
                $idParamClass = $idParam->getType();
                if ($idParamClass) {
                    return $idParamClass->getName();
                }
            }
        }

        return null;
    }

    private function getReflectionFunctionByController(callable $controller): \ReflectionFunctionAbstract
    {
        if (\is_array($controller)) {
            $r = new \ReflectionMethod($controller[0], $controller[1]);
        } elseif (\is_object($controller) && \is_callable([$controller, '__invoke'])) {
            $r = new \ReflectionMethod($controller, '__invoke');
        } else {
            $r = new \ReflectionFunction($controller);
        }

        return $r;
    }

    private function allowDraftAction($id = null, string $className = null): void
    {
        if (!$id || !$className || !is_subclass_of($className, DraftableInterface::class)) {
            return;
        }

        $em = $this->doctrineHelper->getEntityManagerForClass($className);
        $filters = $em->getFilters();
        if (!$filters->isEnabled(DraftableFilter::FILTER_ID)) {
            return;
        }

        $filters->disable(DraftableFilter::FILTER_ID);
        /** @var DraftableInterface $entity */
        $entity = $em->getRepository($className)->find($id);
        if ($entity && !DraftHelper::isDraft($entity)) {
            $filters->enable(DraftableFilter::FILTER_ID);
        }
    }
}
