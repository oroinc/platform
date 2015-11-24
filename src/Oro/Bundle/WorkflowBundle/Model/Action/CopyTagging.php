<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use LogicException;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CopyTagging extends AbstractAction
{
    const PATH_SOURCE = 'source';
    const PATH_DESTINATION = 'destination';
    const PATH_ORGANIZATION = 'organization';

    /** @var TagManager */
    protected $tagManager;

    /** @var array */
    protected $options = [];

    /**
     * @param ContextAccessor $contextAccessor
     * @param TagManager $tagManager
     */
    public function __construct(ContextAccessor $contextAccessor, TagManager $tagManager)
    {
        parent::__construct($contextAccessor);
        $this->tagManager = $tagManager;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if (!empty($options[static::PATH_SOURCE]) && !$options[static::PATH_SOURCE] instanceof PropertyPathInterface) {
            throw new InvalidParameterException('Source must be valid property definition');
        }

        if (!empty($options[static::PATH_DESTINATION]) &&
            !$options[static::PATH_DESTINATION] instanceof PropertyPathInterface
        ) {
            throw new InvalidParameterException('Destination must be valid property definition');
        }

        $this->options = $options;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $source = $this->getTaggable($context, static::PATH_SOURCE);
        $destination = $this->getTaggable($context, static::PATH_DESTINATION);
        $organization = $this->getOrganization($context);

        $this->tagManager->loadTagging($source, $organization);
        $tags = $this->tagManager->getTags($source);
        $preparedTags = [
            'all'   => $tags->toArray(),
            'owner' => $tags->toArray(),
        ];

        $this->tagManager->setTags($destination, $preparedTags);
        $this->tagManager->saveTagging($destination, true, $organization);
    }

    /**
     * @param mixed $context
     *
     * @return Organization|null
     */
    protected function getOrganization($context)
    {
        if (empty($this->options[static::PATH_ORGANIZATION]) ||
            !$this->options[static::PATH_ORGANIZATION] instanceof PropertyPathInterface
        ) {
            return null;
        }

        return $this->getObject($context, static::PATH_ORGANIZATION);
    }

    /**
     * @param mixed $context
     * @param string $path
     *
     * @return Taggable
     */
    protected function getTaggable($context, $path)
    {
        $object = $this->getObject($context, $path);
        if (!$object instanceof Taggable) {
            throw new LogicException(sprintf(
                'Object in path "%s" in "copy_tagging" action have to implement
                "Oro\Bundle\TagBundle\Entity\TagManager" interface',
                $path
            ));
        }

        return $object;
    }

    /**
     * @param mixed $context
     * @param string $path
     *
     * @return object|null
     */
    protected function getObject($context, $path)
    {
        $object = !empty($this->options[$path])
            ? $this->contextAccessor->getValue($context, $this->options[$path])
            : null;

        return $object;
    }
}
