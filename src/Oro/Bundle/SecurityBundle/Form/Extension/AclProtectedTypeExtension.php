<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AclProtectedTypeExtension extends AbstractTypeExtension
{
    /** @var AclHelper */
    private $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    public function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $aclHelper = $this->aclHelper;
        $loader = function (Options $options) use ($aclHelper) {
            // create simple QB in order to prevent loading all entities from repo by EntityChoiceList
            $qb = (null !== $options['query_builder'])
                ? $options['query_builder']
                : $options['em']->getRepository($options['class'])->createQueryBuilder('e');

            return new AclProtectedQueryBuilderLoader(
                $aclHelper,
                $qb,
                $options['em'],
                $options['class']
            );
        };
        $resolver->setDefaults(['loader' => $loader]);
    }
}
