<?php

namespace Oro\Bundle\ActivityBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

class ActivityEntityApiHandler extends ApiFormHandler
{
    /** @var ActivityManager */
    protected $activityManager;

    /**
     * @param FormInterface   $form
     * @param Request         $request
     * @param ObjectManager   $entityManager
     * @param ActivityManager $activityManager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        ObjectManager $entityManager,
        ActivityManager $activityManager
    ) {
        parent::__construct($form, $request, $entityManager);
        $this->activityManager = $activityManager;
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
}
