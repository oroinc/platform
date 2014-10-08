<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OrganizationFixture extends AbstractTemplateRepository
{
    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var Organization
     */
    protected $defaultOrganization;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\OrganizationBundle\Entity\Organization';
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity($key)
    {
        return new Organization();
    }

    /**
     * @param string       $key
     * @param Organization $entity
     */
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case 'default':
                $organization = $this->securityFacade->getOrganization();
                if ($organization) {
                    $entity->setName($organization->getName());
                }
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
