<?php

namespace Oro\Bundle\WorkflowBundle\Model\Action;

use LogicException;

use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\TagBundle\Entity\Taggable;
use Oro\Bundle\TagBundle\Entity\TagManager;
use Oro\Bundle\WorkflowBundle\Exception\InvalidParameterException;
use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;

class CopyTagging extends AbstractAction
{
    const PATH_SOURCE = 'source';
    const PATH_DESTINATION = 'destination';

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
        $source = $this->getObject($context, static::PATH_SOURCE);
        $destination = $this->getObject($context, static::PATH_DESTINATION);

        $this->tagManager->loadTagging($source);
        $tags = $source->getTags();
        $preparedTags = [
            'all'   => $tags->toArray(),
            'owner' => $tags->toArray(),
        ];

        $destination->setTags($preparedTags);
        $this->tagManager->saveTagging($destination);
    }

    /**
     * @param mixed $context
     *
     * @return Taggable
     */
    protected function getObject($context, $path)
    {
        $object = !empty($this->options[$path])
            ? $this->contextAccessor->getValue($context, $this->options[$path])
            : null;

        if (!$object instanceof Taggable) {
            throw new LogicException(
                'All objects passed to "copy_tagging" action have to implement
                "Oro\Bundle\TagBundle\Entity\TagManager" interface'
            );
        }

        return $object;
    }
}
