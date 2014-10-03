<?php

namespace Oro\Bundle\OrganizationBundle\ImportExport\TemplateFixture;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ImportExportBundle\TemplateFixture\AbstractTemplateRepository;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class OrganizationFixture extends AbstractTemplateRepository
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var Organization
     */
    protected $defaultOrganization;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
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
     * @return Organization
     */
    protected function getDefaultOrganization()
    {
        if (!$this->defaultOrganization) {
            $repository = $this->registry->getRepository('OroOrganizationBundle:Organization');
            $this->defaultOrganization = $repository->findOneBy([], ['id' => 'asc']);
        }

        return $this->defaultOrganization;
    }

    /**
     * @param string       $key
     * @param Organization $entity
     */
    public function fillEntityData($key, $entity)
    {
        switch ($key) {
            case 'default':
                $entity->setName($this->getDefaultOrganization()->getName());
                return;
        }

        parent::fillEntityData($key, $entity);
    }
}
