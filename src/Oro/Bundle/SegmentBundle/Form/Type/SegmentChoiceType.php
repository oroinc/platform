<?php

namespace Oro\Bundle\SegmentBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Covers logic of selecting segment for some entity.
 */
class SegmentChoiceType extends AbstractType
{
    private ManagerRegistry $doctrine;

    private AclHelper $aclHelper;

    public function __construct(ManagerRegistry $doctrine, AclHelper $aclHelper)
    {
        $this->doctrine = $doctrine;
        $this->aclHelper = $aclHelper;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => 'oro.segment.form.segment_choice.placeholder',
        ]);

        $resolver
            ->define('entityChoices')
            ->default(false)
            ->allowedTypes('bool');

        $resolver
            ->define('entityClass')
            ->required()
            ->allowedTypes('null', 'string');

        $resolver->setDefault('choices', function (Options $options) {
            $segmentRepository = $this->doctrine->getRepository(Segment::class);

            if ($options['entityChoices']) {
                return $segmentRepository->findSegmentsByEntity($this->aclHelper, $options['entityClass']);
            }

            return $segmentRepository->findByEntity($this->aclHelper, $options['entityClass']);
        });

        $resolver->setDefault('choice_label', function (Options $options, $previousValue) {
            return !empty($options['entityChoices']) ? 'name' : $previousValue;
        });

        $resolver->setDefault('choice_value', function (Options $options, $previousValue) {
            return !empty($options['entityChoices']) ? 'id' : $previousValue;
        });
    }

    #[\Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }
}
