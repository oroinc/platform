<?php

namespace Oro\Bundle\IntegrationBundle\Form\Type;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class IntegrationSelectType extends AbstractType
{
    const NAME = 'oro_integration_select';

    /** @var EntityManager */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that    = $this;
        $choices = function (Options $options) use ($that) {
            return $that->getChoices($options);
        };

        $resolver->setDefaults(
            [
                'empty_value' => '',
                'choices'     => $choices,
                'configs'     => [
                    'placeholder' => 'oro.form.choose_value',
//                    'result_template_twig'    => 'OroIntegrationBundle:Autocomplete:select/result.html.twig',
//                    'selection_template_twig' => 'OroIntegrationBundle:Autocomplete:select/selection.html.twig',
                ]
            ]
        );
        $resolver->setOptional(['allowed_types']);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * @param Options $options
     *
     * @return array
     */
    protected function getChoices(Options $options)
    {
        $types = null;
        if ($options->has('allowed_types')) {
            $types = $options->get('allowed_types');
            $types = is_array($types) ? $types : [$types];
            $types = array_unique($types);
        }

        $qb = $this->em->createQueryBuilder();
        $qb->select('i');
        $qb->from('OroIntegrationBundle:Channel', 'i');
        $qb->orderBy('i.name', 'ASC');

        if (null !== $types) {
            if (!empty($types)) {
                $qb->andWhere($qb->expr()->in('i.type', $types));
            } else {
                $qb->andWhere('1 = 0');
            }
        }

        $results = $qb->getQuery()->getResult();

        return $results;
    }
}
