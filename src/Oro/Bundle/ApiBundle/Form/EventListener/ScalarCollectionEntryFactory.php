<?php

namespace Oro\Bundle\ApiBundle\Form\EventListener;

use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ScalarCollectionEntryFactory extends CollectionEntryFactory
{
    /** @var string */
    protected $dataProperty;

    /**
     * @param string $dataClass
     * @param string $dataProperty
     * @param string $type
     * @param array  $options
     */
    public function __construct($dataClass, $dataProperty, $type, array $options = [])
    {
        parent::__construct($dataClass, $type, $options);
        $this->dataProperty = $dataProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function createEntry(FormFactoryInterface $factory, $name)
    {
        $entryTypeBuilder = $factory->createNamedBuilder(
            $name,
            FormType::class,
            null,
            [
                'auto_initialize' => false,
                'data_class'      => $this->dataClass,
                'property_path'   => '[' . $name . ']',
                'error_bubbling'  => false,
                'constraints'     => new Assert\Valid()
            ]
        );
        $entryTypeBuilder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $event->setData([$this->dataProperty => $event->getData()]);
            }
        );

        $entryType = $entryTypeBuilder->getForm();
        $entryType->add(
            $this->dataProperty,
            $this->type,
            array_replace(
                ['error_bubbling' => true],
                $this->options
            )
        );

        return $entryType;
    }
}
