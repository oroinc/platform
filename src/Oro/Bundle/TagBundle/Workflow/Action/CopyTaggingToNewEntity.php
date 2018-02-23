<?php

namespace Oro\Bundle\TagBundle\Workflow\Action;

use LogicException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\TagBundle\Entity\Tagging;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\TagBundle\Helper\TaggableHelper;
use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * This class was provided for backwards compatibility.
 * In future CopyTagging will be refactored to not check ACL permissions.
 *
 * Now this class can be used in contexts where security token is not available.
 *
 * Also for simplicity it just copies tags, without checking that some tags already exist.
 */
class CopyTaggingToNewEntity extends AbstractAction
{
    const PATH_SOURCE       = 'source';
    const PATH_DESTINATION  = 'destination';
    const PATH_ORGANIZATION = 'organization';

    /** @var TagManager */
    protected $tagManager;

    /** @var TaggableHelper */
    protected $taggableHelper;

    /** @var array */
    protected $options = [];

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param ContextAccessor $contextAccessor
     * @param TagManager      $tagManager
     * @param TaggableHelper  $taggableHelper
     * @param DoctrineHelper  $doctrineHelper
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        TagManager $tagManager,
        TaggableHelper $taggableHelper,
        DoctrineHelper $doctrineHelper
    ) {
        parent::__construct($contextAccessor);
        $this->tagManager     = $tagManager;
        $this->taggableHelper = $taggableHelper;
        $this->doctrineHelper = $doctrineHelper;
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
        $em = $this->doctrineHelper->getEntityManagerForClass(Tagging::class);

        $source       = $this->getTaggable($context, static::PATH_SOURCE);
        $destination  = $this->getTaggable($context, static::PATH_DESTINATION);
        $organization = $this->getOrganization($context);

        $this->tagManager->loadTagging($source, $organization);
        $tags = $this->tagManager->getTags($source);

        $this->tagManager->setTags($destination, $tags);

        foreach ($tags as $tag) {
            $tagging = new Tagging($tag, $destination);
            $em->persist($tagging);
        }

        $em->flush();
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
     * @param mixed  $context
     * @param string $path
     *
     * @return object
     */
    protected function getTaggable($context, $path)
    {
        $object = $this->getObject($context, $path);

        if (!$this->taggableHelper->isTaggable($object)) {
            throw new LogicException(
                sprintf(
                    'Object in path "%s" in "copy_tagging_to_new_entity" action should be taggable.',
                    $path
                )
            );
        }

        return $object;
    }

    /**
     * @param mixed  $context
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
