<?php

namespace Oro\Bundle\ActivityBundle\Entity\Manager;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SearchBundle\Engine\ObjectMapper;
use Oro\Bundle\SoapBundle\Entity\Manager\ApiEntityManager;

class ActivityContextApiEntityManager extends ApiEntityManager
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var TokenStorageInterface */
    protected $securityTokenStorage;

    /** @var ConfigManager */
    protected $configManager;

    /** @var RouterInterface */
    protected $router;

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var ObjectMapper */
    protected $mapper;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ObjectManager         $om
     * @param ActivityManager       $activityManager
     * @param TokenStorageInterface $securityTokenStorage
     * @param ConfigManager         $configManager
     * @param RouterInterface       $router
     * @param EntityAliasResolver   $entityAliasResolver
     * @param ObjectMapper          $objectMapper
     * @param TranslatorInterface   $translator
     * @param DoctrineHelper        $doctrineHelper
     */
    public function __construct(
        ObjectManager $om,
        ActivityManager $activityManager,
        TokenStorageInterface $securityTokenStorage,
        ConfigManager $configManager,
        RouterInterface $router,
        EntityAliasResolver $entityAliasResolver,
        ObjectMapper $objectMapper,
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct(null, $om);

        $this->activityManager      = $activityManager;
        $this->securityTokenStorage = $securityTokenStorage;
        $this->configManager        = $configManager;
        $this->router               = $router;
        $this->entityAliasResolver  = $entityAliasResolver;
        $this->mapper               = $objectMapper;
        $this->translator           = $translator;
        $this->doctrineHelper       = $doctrineHelper;
    }

    /**
     * Returns the context for the given activity class and id
     *
     * @param string $class The FQCN of the activity entity
     * @param        $id
     *
     * @return array
     */
    public function getActivityContext($class, $id)
    {
        $currentUser = $this->securityTokenStorage->getToken()->getUser();
        $userClass   = ClassUtils::getClass($currentUser);
        $entity      = $this->doctrineHelper->getEntity($class, $id);
        $result = [];

        if (!$entity || !$entity instanceof ActivityInterface) {
            return $result;
        }

        $targets = $entity->getActivityTargetEntities();
        $entityProvider = $this->configManager->getProvider('entity');

        foreach ($targets as $target) {
            $targetClass = ClassUtils::getClass($target);
            $targetId = $target->getId();

            if ($userClass === $targetClass && $currentUser->getId() === $targetId) {
                continue;
            }

            $item          = [];
            $config        = $entityProvider->getConfig($targetClass);
            $metadata      = $this->configManager->getEntityMetadata($targetClass);
            $safeClassName = $this->entityClassNameHelper->getUrlSafeClassName($targetClass);

            $link = null;
            if ($metadata) {
                $link = $this->router->generate($metadata->getRoute(), ['id' => $targetId]);
            } elseif ($link === null && ExtendHelper::isCustomEntity($targetClass)) {
                // Generate view link for the custom entity
                $link = $this->router->generate(
                    'oro_entity_view',
                    [
                        'id'         => $targetId,
                        'entityName' => $safeClassName

                    ]
                );
            }

            if ($fields = $this->mapper->getEntityMapParameter($targetClass, 'title_fields')) {
                $text = [];
                foreach ($fields as $field) {
                    $text[] = $this->mapper->getFieldValue($target, $field);
                }
                $item['title'] = implode(' ', $text);
            } else {
                $item['title'] = $this->translator->trans('oro.entity.item', ['%id%' => $targetId]);
            }

            $item['activityClassAlias'] = $this->entityAliasResolver->getPluralAlias($class);
            $item['entityId']           = $id;

            $item['targetId']        = $targetId;
            $item['targetClassName'] = $safeClassName;

            $item['icon'] = $config->get('icon');
            $item['link'] = $link;

            $result[] = $item;
        }

        return $result;
    }
}
