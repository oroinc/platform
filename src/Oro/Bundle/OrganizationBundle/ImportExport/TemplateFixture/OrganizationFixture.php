<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides template fixture data for organization import/export operations.
 *
 * This fixture generates sample organization data for import templates, allowing users to
 * understand the expected format for bulk importing organizations. It uses the current user's
 * organization as the default template data, ensuring the fixture reflects the actual
 * organizational context.
 */
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
