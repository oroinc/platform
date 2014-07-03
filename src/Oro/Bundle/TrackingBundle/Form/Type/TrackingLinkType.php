<?php


namespace Oro\Bundle\TrackingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;

class TrackingLinkType extends AbstractType
{
    // @todo BAP-4696 use template

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'text';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_tracking_link_type';
    }
}
