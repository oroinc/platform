<?php

namespace Oro\Bundle\EntityExtendBundle\Entity\EntityListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Bundle\EntityExtendBundle\Entity\AttributeGroup;
use Oro\Bundle\EntityExtendBundle\Generator\SlugGenerator;

class AttributeGroupListener
{
    const DEFAULT_SLUG = 'default_group';

    /** @var SlugGenerator */
    private $slugGenerator;

    /**
     * @param SlugGenerator $slugGenerator
     */
    public function __construct(SlugGenerator $slugGenerator)
    {
        $this->slugGenerator = $slugGenerator;
    }

    /**
     * @param AttributeGroup $group
     * @param LifecycleEventArgs $args
     */
    public function prePersist(AttributeGroup $group, LifecycleEventArgs $args)
    {
        if ($group->getCode()) {
            return;
        }

        $codeSlug = $this->slugGenerator->slugify((string)$group->getDefaultLabel()) ?: self::DEFAULT_SLUG;

        $repository = $args->getEntityManager()->getRepository(AttributeGroup::class);
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
