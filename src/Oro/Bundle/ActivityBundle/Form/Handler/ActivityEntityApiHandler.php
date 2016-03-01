<?php

namespace Oro\Bundle\ActivityBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ActivityEntityApiHandler extends ApiFormHandler
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param FormInterface   $form
     * @param Request         $request
     * @param ObjectManager   $entityManager
     * @param ActivityManager $activityManager
     * @param SecurityFacade  $securityFacade
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $entityManager,
        ActivityManager $activityManager,
        SecurityFacade $securityFacade
    ) {
        parent::__construct($form, $request, $entityManager);
        $this->activityManager = $activityManager;
        $this->securityFacade  = $securityFacade;
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
        if (!$this->securityFacade->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException();
        }
    }
}
