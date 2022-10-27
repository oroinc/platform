<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Covers logic of selecting segment for some entity.
 */
class SegmentChoiceType extends AbstractType
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AclHelper */
    private $aclHelper;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
        ]);
        $resolver->setRequired('entityClass');
        $resolver->setNormalizer(
            'choices',
            function (OptionsResolver $options) {
                return $this->doctrine->getManagerForClass(Segment::class)
                    ->getRepository(Segment::class)
                    ->findByEntity($this->aclHelper, $options['entityClass']);
            }
        );
        $resolver->setAllowedTypes('entityClass', ['null', 'string']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
