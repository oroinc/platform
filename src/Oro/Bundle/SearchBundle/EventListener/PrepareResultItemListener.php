<?php

namespace Oro\Bundle\SearchBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SearchBundle\Event\PrepareResultItemEvent;
use Oro\Bundle\SearchBundle\Query\Result\Item as ResultItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends data that presents in search results.
 */
class PrepareResultItemListener
{
    private UrlGeneratorInterface $urlGenerator;
    private ObjectMapper $mapper;
    private ManagerRegistry $doctrine;
    private ConfigManager $configManager;
    private TranslatorInterface $translator;

    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        ObjectMapper $mapper,
        ManagerRegistry $doctrine,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->mapper = $mapper;
        $this->doctrine = $doctrine;
        $this->configManager = $configManager;
        $this->translator = $translator;
    }

    public function process(PrepareResultItemEvent $event): void
    {
        $entity = $event->getEntity();
        $item = $event->getResultItem();

        if (!$item->getRecordUrl()) {
            $item->setRecordUrl($this->getEntityUrl($entity, $item));
        }

        if (!$item->getEntityLabel()) {
            $className = $item->getEntityName();
            $label = (string) $this->configManager->getEntityConfig('entity', $className)->get('label');
            $item->setEntityLabel($this->translator->trans($label));
        }
    }

    private function getEntityUrl(?object $entity, ResultItem $item): string
    {
        $className = $item->getEntityName();
        if (!$this->mapper->getEntityMapParameter($className, 'route')) {
            return '';
        }

        $routeParameters = $this->mapper->getEntityMapParameter($className, 'route');
        $routeData = [];

        if (!empty($routeParameters['parameters'])) {
            /**
             * NOTE: possible to generate url without entity object if only identifier field needed
             */
            $em = $this->doctrine->getManagerForClass($className);
            $identifierField = $em->getClassMetadata($className)->getSingleIdentifierFieldName();
            $idKey = array_search($identifierField, $routeParameters['parameters'], true);
            $needToHaveEntity = $idKey === false || count($routeParameters['parameters']) > 1;

            if (!$entity && $needToHaveEntity) {
                $entity = $em->find($className, $item->getRecordId());
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

        return $this->urlGenerator->generate(
            (string) $routeParameters['name'],
            $routeData,
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }
}
