<?php

namespace Oro\Bundle\ActivityBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Event\PrepareContextTitleEvent;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;

class ContextsToViewTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ObjectMapper */
    protected $mapper;

    /* @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * @param EntityManager         $entityManager
     * @param ConfigManager         $configManager
     * @param TranslatorInterface   $translator
     * @param ObjectMapper          $mapper
     * @param TokenStorageInterface $securityTokenStorage
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        TokenStorageInterface $securityTokenStorage,
        EventDispatcherInterface $dispatcher
    ) {
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->translator           = $translator;
        $this->mapper               = $mapper;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->dispatcher           = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (!$value) {
            return '';
        }

        if (is_array($value)) {
            $result = [];
            $user   = $this->securityTokenStorage->getToken()->getUser();
            foreach ($value as $target) {
                // Exclude current user
                $targetClass = ClassUtils::getClass($target);
                if (ClassUtils::getClass($user) === $targetClass &&
                    $user->getId() === $target->getId()
                ) {
                    continue;
                }

                if ($fields = $this->mapper->getEntityMapParameter($targetClass, 'title_fields')) {
                    $text = [];
                    foreach ($fields as $field) {
                        $text[] = $this->mapper->getFieldValue($target, $field);
                    }
                } elseif (method_exists($target, '__toString')) {
                    $text = [(string) $target];
                } else {
                    $text = [$this->translator->trans('oro.entity.item', ['%id%' => $target->getId()])];
                }
                $text = implode(' ', $text);
                if ($label = $this->getClassLabel($targetClass)) {
                    $text .= ' (' . $label . ')';
                }

                $item['title'] = $text;
                $item['targetId'] = $target->getId();
                $event = new PrepareContextTitleEvent($item, $targetClass);
                $this->dispatcher->dispatch(PrepareContextTitleEvent::EVENT_NAME, $event);
                $item = $event->getItem();
                $text = $item['title'];

                $result[] = json_encode($this->getResult($text, $target));
            }

            $value = implode(';', $result);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        $targets = explode(';', $value);
        $result  = [];
        $filters = [];

        foreach ($targets as $target) {
            $target = json_decode($target, true);
            if (array_key_exists('entityClass', $target) === true && array_key_exists('entityId', $target)) {
                if (!isset($filters[$target['entityClass']])) {
                    $filters[$target['entityClass']] = [];
                }
                $filters[$target['entityClass']][] = $target['entityId'];
            }
        }

        foreach ($filters as $entityClass => $ids) {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            $entities = $this->entityManager->getRepository($metadata->getName())->findBy(
                ['id' => $ids]
            );
            $result   = array_merge($result, $entities);
        }

        return $result;
    }

    /**
     * @param string $className - FQCN
     *
     * @return string|null
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $label = $this->configManager->getProvider('entity')->getConfig($className)->get('label');

        return $this->translator->trans($label);
    }

    /**
     * @param string $text
     * @param object $object
     *
     * @return array
     */
    protected function getResult($text, $object)
    {
        return [
            'text' => $text,
            'id'   => json_encode([
                'entityClass' => ClassUtils::getClass($object),
                'entityId'    => $object->getId(),
            ])
        ];
    }
}
