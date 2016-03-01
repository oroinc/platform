<?php

namespace Oro\Bundle\ActionBundle\Helper;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class ContextHelper
{
    const ROUTE_PARAM = 'route';
    const ENTITY_ID_PARAM = 'entityId';
    const ENTITY_CLASS_PARAM = 'entityClass';
    const DATAGRID_PARAM = 'datagrid';
    const GROUP_PARAM = 'group';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var RequestStack */
    protected $requestStack;

    /** @var array */
    protected $actionDatas = [];

    /** @var  PropertyAccessor */
    protected $propertyAccessor;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param RequestStack $requestStack
     */
    public function __construct(DoctrineHelper $doctrineHelper, RequestStack $requestStack = null)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->requestStack = $requestStack;
    }

    /**
     * @param array|null $context
     * @return array
     */
    public function getContext(array $context = null)
    {
        if (null === $context) {
            $currentRequest = $this->requestStack->getCurrentRequest();
            $context = $currentRequest ? [
                self::ROUTE_PARAM => $currentRequest->get(self::ROUTE_PARAM),
                self::ENTITY_ID_PARAM => $currentRequest->get(self::ENTITY_ID_PARAM),
                self::ENTITY_CLASS_PARAM => $currentRequest->get(self::ENTITY_CLASS_PARAM),
                self::DATAGRID_PARAM => $currentRequest->get(self::DATAGRID_PARAM),
                self::GROUP_PARAM => $currentRequest->get(self::GROUP_PARAM)
            ] : [];
        }

        return $this->normalizeContext($context);
    }

    /**
     * @param array|null $context
     * @return ActionData
     */
    public function getActionData(array $context = null)
    {
        $context = $this->getContext($context);

        $hash = $this->generateHash($context, [self::ENTITY_CLASS_PARAM, self::ENTITY_ID_PARAM]);

        if (!array_key_exists($hash, $this->actionDatas)) {
            $entity = null;

            if ($context['entityClass']) {
                $entity = $this->getEntityReference(
                    $context[self::ENTITY_CLASS_PARAM],
                    $context[self::ENTITY_ID_PARAM]
                );
            }

            $this->actionDatas[$hash] = new ActionData(['data' => $entity]);
        }

        return $this->actionDatas[$hash];
    }

    /**
     * @param array $context
     * @return array
     */
    protected function normalizeContext(array $context)
    {
        return array_merge(
            [
                self::ROUTE_PARAM => null,
                self::ENTITY_ID_PARAM => null,
                self::ENTITY_CLASS_PARAM => null,
                self::DATAGRID_PARAM => null,
                self::GROUP_PARAM => null
            ],
            $context
        );
    }

    /**
     * @param string $entityClass
     * @param mixed $entityId
     * @return Object
     */
    protected function getEntityReference($entityClass, $entityId)
    {
        $entity = null;

        if ($this->doctrineHelper->isManageableEntity($entityClass)) {
            if ($entityId) {
                $entity = $this->doctrineHelper->getEntityReference($entityClass, $entityId);
            } else {
                $entity = $this->doctrineHelper->createEntityInstance($entityClass);
            }
        }

        return $entity;
    }

    /**
     * @param array $context
     * @param array $properties
     * @return string
     */
    protected function generateHash(array $context, array $properties)
    {
        $array = [];
        foreach ($properties as $property) {
            $array[$property] = $this->getPropertyAccessor()->getValue($context, sprintf('[%s]', $property));
            if (is_array($array[$property])) {
                ksort($array[$property]);
            }
        }
        ksort($array);

        return md5(json_encode($array, JSON_NUMERIC_CHECK));
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
