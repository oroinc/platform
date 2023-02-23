<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The factory to create a form for a collection of entities.
 */
class CollectionEntryFactory
{
    private string $dataClass;
    private string $type;
    private array $options;

    public function __construct(string $dataClass, string $type, array $options = [])
    {
        $this->dataClass = $dataClass;
        $this->type = $type;
        $this->options = $options;
    }

    /**
     * Creates a form for the collection entry.
     *
     * @param FormFactoryInterface $factory The form factory
     * @param string               $name    The collection entry field name
     *
     * @return FormInterface
     */
    public function createEntry(FormFactoryInterface $factory, string $name): FormInterface
    {
        return $factory->createNamed(
            $name,
            $this->type,
            null,
            array_replace(
                [
                    'auto_initialize' => false,
                    'data_class'      => $this->dataClass,
                    'property_path'   => '[' . $name . ']',
                    'error_bubbling'  => false,
                    'constraints'     => new Assert\Valid()
                ],
                $this->options
            )
        );
    }
}
