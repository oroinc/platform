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

    /**
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(TokenAccessorInterface $tokenAccessor)
    {
        $this->tokenAccessor = $tokenAccessor;
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
                $organization = $this->tokenAccessor->getOrganization();
                if ($organization) {
                    $entity->setName($organization->getName());
                }
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
