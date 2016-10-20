<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;

class PrepareResultItemListener
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var ObjectMapper
     */
    protected $mapper;

    /**
     * @var EntityManager
     */
    protected $em;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * Constructor
     *
     * @param Router $router
     * @param ObjectMapper $mapper
     * @param EntityManager $em
     * @param EntityNameResolver $entityNameResolver
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Router $router,
        ObjectMapper $mapper,
        EntityManager $em,
        EntityNameResolver $entityNameResolver,
        TranslatorInterface $translator
    ) {
        $this->router = $router;
        $this->mapper = $mapper;
        $this->em = $em;
        $this->entityNameResolver = $entityNameResolver;
        $this->translator = $translator;
    }

    /**
     * Process event
     *
     * @param PrepareResultItemEvent $event
     */
    public function process(PrepareResultItemEvent $event)
    {
        $entity = $event->getEntity();
        $item = $event->getResultItem();

        if (!$item->getRecordUrl()) {
            $item->setRecordUrl($this->getEntityUrl($entity, $item));
        }

        if (!$item->getRecordTitle()) {
            $item->setRecordTitle($this->getEntityTitle($entity, $item));
        }
    }

    /**
     * Get url for entity
     *
     * @param object $entity
     * @param $item \Oro\Bundle\SearchBundle\Query\Result\Item
     * @return string
     */
    protected function getEntityUrl($entity, $item)
    {
        $name = $item->getEntityName();

        $entityMeta = $this->em->getClassMetadata($name);
        $identifierField = $entityMeta->getSingleIdentifierFieldName($entityMeta);

        if ($this->mapper->getEntityMapParameter($name, 'route')) {
            $routeParameters = $this->mapper->getEntityMapParameter($name, 'route');
            $routeData = array();

            if ($this->isParametersDefined($routeParameters)) {
                /**
                 * NOTE: possible to generate url without entity object if only identifier field needed
                 */
                $idKey = array_search($identifierField, $routeParameters['parameters']);
                $needToHaveEntity = $idKey === false || count($routeParameters['parameters']) > 1;

                if (!$entity && $needToHaveEntity) {
                    $entity = $this->em->getRepository($name)->find($item->getRecordId());
                }

                foreach ($routeParameters['parameters'] as $parameter => $field) {
                    if ($entity) {
                        if (substr_count($field, '@') === 2) {
                            $routeData[$parameter] = str_replace('@', '', $field);
                        } else {
                            $routeData[$parameter] = $this->mapper->getFieldValue($entity, $field);
                        }
                    } else {
                        $routeData[$parameter] = $item->getRecordId();
                    }
                }
            }

            return $this->router->generate(
                $routeParameters['name'],
                $routeData,
                true
            );
        }

        return '';
    }

    /**
     * Get entity string
     *
     * @param $entity object
     * @param $item \Oro\Bundle\SearchBundle\Query\Result\Item
     *
     * @return string
     */
    protected function getEntityTitle($entity, $item)
    {
        $name = $item->getEntityName();

        if (!$entity) {
            $entity = $this->em->getRepository($name)->find($item->getRecordId());
        }

        return $this->entityNameResolver->getName($entity)
            ?: $this->translator->trans('oro.entity.item', ['%id%' => $entity->getId()]);
    }

    /**
     * Check if route parameters defined and not empty
     *
     * @param array $data
     * @return bool
     */
    protected function isParametersDefined(array $data)
    {
        return isset($data['parameters']) && count($data['parameters']);
    }
}
