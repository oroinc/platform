<?php

namespace Oro\Bundle\EntityBundle\Routing;

use Symfony\Component\Routing\Route;
use Symfony\Component\Translation\TranslatorInterface;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class DictionaryEntityApiDocHandler implements HandlerInterface
{
    const DESCRIPTION_TEMPLATE = 'Get {plural_name}';
    const DOCUMENTATION_TEMPLATE = 'Get {plural_name}';
    const FALLBACK_DESCRIPTION_TEMPLATE = 'Get values of {class}';
    const FALLBACK_DOCUMENTATION_TEMPLATE = 'Get values of {class}';

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param EntityAliasResolver $entityAliasResolver
     * @param ConfigManager       $configManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        EntityAliasResolver $entityAliasResolver,
        ConfigManager $configManager,
        TranslatorInterface $translator
    ) {
        $this->entityAliasResolver = $entityAliasResolver;
        $this->configManager       = $configManager;
        $this->translator          = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function handle(ApiDoc $annotation, array $annotations, Route $route, \ReflectionMethod $method)
    {
        if ($route->getOption('group') !== DictionaryEntityRouteOptionsResolver::ROUTE_GROUP) {
            return;
        }

        $pluralAlias = $route->getDefault(DictionaryEntityRouteOptionsResolver::ENTITY_ATTRIBUTE);
        if (!$pluralAlias) {
            return;
        }

        $className = $this->entityAliasResolver->getClassByPluralAlias($pluralAlias);

        $pluralName = $this->getEntityPluralName($className);
        if ($pluralName) {
            $annotation->setDescription(
                strtr(static::DESCRIPTION_TEMPLATE, ['{plural_name}' => $pluralName])
            );
            $annotation->setDocumentation(
                strtr(static::DOCUMENTATION_TEMPLATE, ['{plural_name}' => $pluralName])
            );
        } else {
            $annotation->setDescription(
                strtr(static::FALLBACK_DESCRIPTION_TEMPLATE, ['{class}' => $className])
            );
            $annotation->setDocumentation(
                strtr(static::FALLBACK_DOCUMENTATION_TEMPLATE, ['{class}' => $className])
            );
        }
    }

    /**
     * @param string $className
     *
     * @return string|null
     */
    protected function getEntityPluralName($className)
    {
        $entityConfigProvider = $this->configManager->getProvider('entity');
        if ($entityConfigProvider->hasConfig($className)) {
            $entityConfig = $entityConfigProvider->getConfig($className);
            $label        = $entityConfig->get('plural_label');
            $translated   = $this->translator->trans($label, [], null, 'en');
            if ($translated && $translated !== $label) {
                return strtolower($translated);
            }
        }

        return null;
    }
}
