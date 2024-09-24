<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

class OrganizationFixture extends AbstractTemplateRepository
{
    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var Organization */
    protected $defaultOrganization;

    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
    }

    #[\Override]
    public function getEntityClass()
    {
        return 'Oro\Bundle\OrganizationBundle\Entity\Organization';
    }

    #[\Override]
    protected function createEntity($key)
    {
        return new Organization();
    }

    /**
     * @param string       $key
     * @param Organization $entity
     */
    #[\Override]
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case 'default':
                $organization = $this->tokenAccessor->getOrganization();
                if ($organization) {
                    $entity->setName($organization->getName());
                }
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
