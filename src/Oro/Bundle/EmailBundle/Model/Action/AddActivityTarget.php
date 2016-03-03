<?php

namespace Oro\Bundle\EmailBundle\Model\Action;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailActivityManager;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Exception\InvalidParameterException;
use Oro\Component\Action\Model\ContextAccessor;

/**
 * Class AddActivityTarget
 *
 * @add_email_activity_target:
 *      email: $.emailEntity
 *      target_entity: $.targetEntity
 *      attribute: $.attribute              # status if activity was added is stored in this optional attribute
 *
 * @package Oro\Bundle\ActivityBundle\Model\Action
 */
class AddActivityTarget extends AbstractAction
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var string */
    protected $activityEntity;

    /** @var string */
    protected $targetEntity;

    /** @var string */
    protected $attribute;

    /** @var ActivityListChainProvider */
    private $chainProvider;

    /** @var EntityManager */
    private $entityManager;

    /**
     * @param ContextAccessor           $contextAccessor
     * @param EmailActivityManager      $activityManager
     * @param ActivityListChainProvider $chainProvider
     * @param EntityManager             $entityManager
     */
    public function __construct(
        ContextAccessor $contextAccessor,
        EmailActivityManager $activityManager,
        ActivityListChainProvider $chainProvider,
        EntityManager $entityManager
    ) {
        parent::__construct($contextAccessor);
        $this->activityManager = $activityManager;
        $this->chainProvider = $chainProvider;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    protected function executeAction($context)
    {
        $email = $this->contextAccessor->getValue($context, $this->activityEntity);
        $targetEntity = $this->contextAccessor->getValue($context, $this->targetEntity);

        $activityList = $this->chainProvider->getActivityListEntitiesByActivityEntity($email);
        if ($activityList) {
            $this->entityManager->persist($activityList);
        }
        $result = $this->activityManager->addAssociation($email, $targetEntity);

        if ($this->attribute !== null) {
            $this->contextAccessor->setValue($context, $this->attribute, $result);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        if ((count($options) < 2) || (count($options) > 3)) {
            throw new InvalidParameterException('Two or three parameters are required.');
        }

        if (isset($options['email'])) {
            $this->activityEntity = $options['email'];
        } elseif (isset($options[0])) {
            $this->activityEntity = $options[0];
        } else {
            throw new InvalidParameterException('Parameter "email" has to be set.');
        }

        if (isset($options['target_entity'])) {
            $this->targetEntity = $options['target_entity'];
        } elseif (isset($options[1])) {
            $this->targetEntity = $options[1];
        } else {
            throw new InvalidParameterException('Parameter "target_entity" has to be set.');
        }

        if (isset($options['attribute'])) {
            $this->attribute = $options['attribute'];
        } elseif (isset($options[2])) {
            $this->attribute = $options[2];
        }
    }
}
