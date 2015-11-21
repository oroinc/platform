<?php

namespace Oro\Bundle\TagBundle\Form\Transformer;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\DataTransformerInterface;

use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;

class TagTransformer implements DataTransformerInterface
{
    /**  @var TagManager */
    protected $manager;

    /** @var Taggable */
    protected $entity;

    /** @var Organization|null */
    protected $organization;

    public function __construct(TagManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        // transform to JSON if we have array of entities
        // needed to correct rendering form if validation not passed
        if (is_array($value)) {
            $result = array();
            if ($this->entity) {
                $result = $this->manager->getPreparedArray(
                    $this->entity,
                    new ArrayCollection($value),
                    $this->organization
                );
            }
            $value = json_encode($result);
        }

        return $value;
    }

    /**
     * Setter for entity object
     *
     * @param Taggable $entity
     */
    public function setEntity(Taggable $entity)
    {
        $this->entity = $entity;
    }

    /**
     * @param Organization $organization
     */
    public function setOrganization(Organization $organization = null)
    {
        $this->organization = $organization;
    }
}
