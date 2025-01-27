<?php

namespace Oro\Bundle\FormBundle\Resolver;

use Symfony\Component\Form\FormFactoryInterface;

/**
 * Submits form to get filled up new entity based on provided form data and form type class
 */
class EntityFormResolver implements EntityFormResolverInterface
{
    private array $resolved = [];

    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {
    }

    public function resolve(string $formTypeClass, object $entity, array $entityData): object
    {
        $cacheKey = base64_encode(serialize($entityData));

        if (isset($this->resolved[$cacheKey])) {
            return $this->resolved[$cacheKey];
        }

        $form = $this->formFactory->create($formTypeClass, $entity);

        $form->submit($entityData);

        $this->resolved[$cacheKey] = $form->getData();

        return $this->resolved[$cacheKey];
    }
}
