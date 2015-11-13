<?php

namespace Oro\Bundle\ActivityBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

    /**
     * @param EntityManager         $entityManager
     * @param ConfigManager         $configManager
     * @param TranslatorInterface   $translator
     * @param ObjectMapper          $mapper
     * @param TokenStorageInterface $securityTokenStorage
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        TokenStorageInterface $securityTokenStorage
    ) {
        $this->entityManager        = $entityManager;
        $this->configManager        = $configManager;
        $this->translator           = $translator;
        $this->mapper               = $mapper;
        $this->securityTokenStorage = $securityTokenStorage;
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
                if (ClassUtils::getClass($user) === ClassUtils::getClass($target) &&
                    $user->getId() === $target->getId()
                ) {
                    continue;
                }

                if ($fields = $this->mapper->getEntityMapParameter(ClassUtils::getClass($target), 'title_fields')) {
                    $text = [];
                    foreach ($fields as $field) {
                        $text[] = $this->mapper->getFieldValue($target, $field);
                    }
                } else {
                    $text = [(string)$target];
                }
                $text = implode(' ', $text);
                if ($label = $this->getClassLabel(ClassUtils::getClass($target))) {
                    $text .= ' (' . $label . ')';
                }

                $result[] = json_encode(
                    [
                        'text' => $text,
                        'id'   => json_encode([
                            'entityClass' => ClassUtils::getClass($target),
                            'entityId'    => $target->getId(),
                        ])
                    ]
                );
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
}
