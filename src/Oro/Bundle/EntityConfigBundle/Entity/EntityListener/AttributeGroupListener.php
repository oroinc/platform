<?php

namespace Oro\Bundle\EntityConfigBundle\Entity\EntityListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Generator\SlugGenerator;

/**
 * Listens to AttributeGroup Entity and generates code slug for AttributeGroup
 */
class AttributeGroupListener
{
    const DEFAULT_SLUG = 'default_group';

    /** @var SlugGenerator */
    private $slugGenerator;

    public function __construct(SlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    public function prePersist(AttributeGroup $group, LifecycleEventArgs $args)
    {
        if ($group->getCode()) {
            return;
        }

        $codeSlug = $this->slugGenerator->slugify((string)$group->getDefaultLabel()) ?: self::DEFAULT_SLUG;
        $codeSlug = str_replace('-', '_', $codeSlug);

        $repository = $args->getObjectManager()->getRepository(AttributeGroup::class);
        $i = 0;
        $baseSlug = $codeSlug;
        do {
            $exists = $repository->findOneBy([
                'attributeFamily' => $group->getAttributeFamily(),
                'code' => $codeSlug
            ]);
            if ($exists) {
                $codeSlug = $baseSlug . ++$i;
            }
        } while ($exists);

        $group->setCode($codeSlug);
    }
}
