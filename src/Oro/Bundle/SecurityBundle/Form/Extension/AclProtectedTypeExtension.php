<?php

namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\SecurityBundle\Form\ChoiceList\AclProtectedQueryBuilderLoader;

class AclProtectedTypeExtension extends AbstractTypeExtension
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    /**
     * @param AclHelper $aclHelper
     */
    function __construct(AclHelper $aclHelper)
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
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $that   = $this;
        $loader = function (Options $options) use ($that) {
            if (null !== $options['query_builder']) {
                return new AclProtectedQueryBuilderLoader(
                    $that->aclHelper,
                    $options['query_builder'],
                    $options['em'],
                    $options['class']
                );
            }
        };
        $resolver->setDefaults(['loader' => $loader]);
    }
}
