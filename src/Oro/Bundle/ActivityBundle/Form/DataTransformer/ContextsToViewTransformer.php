<?php

namespace Oro\Bundle\ActivityBundle\Form\DataTransformer;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ContextsToViewTransformer implements DataTransformerInterface
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ObjectMapper */
    protected $mapper;

    /* @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param EntityManager $entityManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param ObjectMapper $mapper
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        EntityManager $entityManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        ObjectMapper $mapper,
        SecurityFacade $securityFacade
    ) {
        $this->entityManager = $entityManager;
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->mapper = $mapper;
        $this->securityFacade = $securityFacade;
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
            $user = $this->securityFacade->getToken()->getUser();
            foreach ($value as $target) {
                if (ClassUtils::getClass($user) === ClassUtils::getClass($target) &&
                    $user->getId() === $target->getId()) {
                    continue;
                }

                if ($fields = $this->mapper->getEntityMapParameter(ClassUtils::getClass($target), 'title_fields')) {
                    $text = [];
                    foreach ($fields as $field) {
                        $text[] = $this->mapper->getFieldValue($target, $field);
                    }
                } else {
                    $text = [(string) $target];
                }
                $text = implode(' ', $text);
                if ($label = $this->getClassLabel(ClassUtils::getClass($target))) {
                    $text .= ' (' . $label . ')';
                }

                $result[] = json_encode(
                    [
                        'text' => $text,
                        'id' => json_encode([
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
        $result = [];
        foreach ($targets as $target) {
            $target = json_decode($target, true);
            if (array_key_exists('entityClass', $target) === true && array_key_exists('entityId', $target)) {
                $metadata = $this->entityManager->getClassMetadata($target['entityClass']);
                $result[] = $this->entityManager->getRepository($metadata->getName())->find($target['entityId']);
            }
        }

        return $result;
    }

    /**
     * @param string $className
     * @return null|string
     */
    protected function getClassLabel($className)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }
        $entityConfig = new EntityConfigId('entity', $className);
        $label = $this->configManager->getConfig($entityConfig)->get('label');

        return $this->translator->trans($label);
    }
}
