<?php
namespace Oro\Bundle\SecurityBundle\Form\Extension;

use Oro\Bundle\SecurityBundle\ChoiceList\AclProtectedQueryBuilderLoader;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AclProtectedTypeExtension extends AbstractTypeExtension
{
    /**
     * @var AclHelper
     */
    private $aclHelper;

    function __construct(AclHelper $aclHelper)
    {
        $this->aclHelper = $aclHelper;
    }


    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'genemu_jqueryselect2_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $type = $this;
        $loader = function (Options $options) use ($type) {
            if (null !== $options['query_builder']) {
                //TODO change this class
                return new AclProtectedQueryBuilderLoader($options['em'], $options['query_builder'], $options['class'], $this->aclHelper);
            }
        };
        $resolver->setDefaults(['loader' => $loader]);
    }
}
