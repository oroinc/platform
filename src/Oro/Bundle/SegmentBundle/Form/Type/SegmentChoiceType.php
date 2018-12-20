<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\SegmentBundle\Entity\Repository\SegmentRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SegmentChoiceType extends AbstractType
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $entityClass;

    /**
     * @param ManagerRegistry $registry
     * @param string          $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass)
    {
        $this->registry = $registry;
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
        ]);
        $resolver->setRequired('entityClass');
        $resolver->setNormalizer(
            'choices',
            function (OptionsResolver $options) {
                /** @var SegmentRepository $repo */
                $repo = $this->registry->getManagerForClass($this->entityClass)->getRepository($this->entityClass);

                return $repo->findByEntity($options['entityClass']);
            }
        );
        $resolver->setAllowedTypes('entityClass', ['null', 'string']);
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }
}
