<?php

namespace Oro\Bundle\ActivityBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ActivityEntityApiHandler extends ApiFormHandler
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /**
     * @param FormInterface                 $form
     * @param RequestStack                  $requestStack
     * @param ObjectManager                 $entityManager
     * @param ActivityManager               $activityManager
     * @param AuthorizationCheckerInterface $authorizationChecker
     */
    public function __construct(
        FormInterface $form,
        RequestStack $requestStack,
        ObjectManager $entityManager,
        ActivityManager $activityManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        parent::__construct($form, $requestStack, $entityManager);
        $this->activityManager = $activityManager;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareFormData($entity)
    {
        $relations = new ArrayCollection();
        $this->form->setData($relations);

        return ['activity' => $entity, 'relations' => $relations];
    }

    /**
     * Process form
     *
     * @param mixed $entity
     *
     * @return mixed|null The instance of saved entity on successful processing; otherwise, null
     */
    public function process($entity)
    {
        $this->checkPermissions($entity);

        return parent::process($entity);
    }

    /**
     * {@inheritdoc}
     */
    protected function onSuccess($entity)
    {
        /** @var ActivityInterface $activity */
        $activity = $entity['activity'];
        /** @var ArrayCollection $relations */
        $relations = $entity['relations'];

        $this->activityManager->addActivityTargets($activity, $relations->toArray());

        $this->entityManager->flush();
    }

    /**
     * @param object $entity
     */
    protected function checkPermissions($entity)
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }
    }
}
