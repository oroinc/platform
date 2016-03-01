<?php

namespace Oro\Bundle\EntityBundle\Routing;

use Symfony\Component\Routing\Route;

use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Nelmio\ApiDocBundle\Extractor\HandlerInterface;

use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;

class DictionaryEntityApiDocHandler implements HandlerInterface
{
    const DESCRIPTION_TEMPLATE = 'Get {plural_name}';
    const DOCUMENTATION_TEMPLATE = 'Get {plural_name}';
    const FALLBACK_DESCRIPTION_TEMPLATE = 'Get values of {class}';
    const FALLBACK_DOCUMENTATION_TEMPLATE = 'Get values of {class}';

    /** @var EntityAliasResolver */
    protected $entityAliasResolver;

    /** @var EntityClassNameProviderInterface */
    protected $entityClassNameProvider;

    /**
     * @param EntityAliasResolver              $entityAliasResolver
     * @param EntityClassNameProviderInterface $entityClassNameProvider
     */
    public function __construct(
        EntityAliasResolver $entityAliasResolver,
        EntityClassNameProviderInterface $entityClassNameProvider
    ) {
        $this->entityAliasResolver     = $entityAliasResolver;
        $this->entityClassNameProvider = $entityClassNameProvider;
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

        $pluralName = $this->entityClassNameProvider->getEntityClassPluralName($className);
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
}
