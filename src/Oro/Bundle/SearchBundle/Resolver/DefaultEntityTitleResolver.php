<?php

namespace Oro\Bundle\SearchBundle\Resolver;

use Symfony\Component\Translation\TranslatorInterface;

class DefaultEntityTitleResolver implements EntityTitleResolverInterface
{
    /** @var EntityTitleResolverInterface $resolver */
    protected $resolver;

    /** @var TranslatorInterface $translator */
    protected $translator;

    /**
     * @param EntityTitleResolverInterface $resolver
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityTitleResolverInterface $resolver, TranslatorInterface $translator)
    {
        $this->resolver = $resolver;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve($entity)
    {
        if ($title = $this->resolver->resolve($entity)) {
            return $title;
        }

        return $this->translator->trans('oro.entity.item', ['%id%' => $entity->getId()]);
    }
}
