<?php

namespace Oro\Bundle\OrganizationBundle\Twig;

use Doctrine\ORM\EntityManager;

class OrganizationExtension extends \Twig_Extension
{
    const EXTENSION_NAME = 'oro_organization';
    const ORGANIZATION_INPUT_TEMPLATE = 'OroOrganizationBundle:Twig:organization.html.twig';

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->entityManager = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_get_login_organizations',
                [$this, 'getLoginOrganizations'],
                ['is_safe' => ['html'], 'needs_environment' => true]
            ),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::EXTENSION_NAME;
    }

    public function getLoginOrganizations(\Twig_Environment $environment, $fieldName, $label, $showLabels)
    {
        $organizations = $this->entityManager->getRepository('OroOrganizationBundle:Organization')->getEnabled();
        return $environment->loadTemplate(self::ORGANIZATION_INPUT_TEMPLATE)->render(
            [
                'organizations' => $organizations,
                'fieldName' => $fieldName,
                'label' => $label,
                'showLabels' => $showLabels
            ]
        );
    }
}
