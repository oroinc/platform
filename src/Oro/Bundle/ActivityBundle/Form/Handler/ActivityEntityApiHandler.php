<?php

namespace Oro\Bundle\ActivityBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\ActivityBundle\Model\ActivityInterface;
use Oro\Bundle\SoapBundle\Form\Handler\ApiFormHandler;

class ActivityEntityApiHandler extends ApiFormHandler
{
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
        /** @var object[] $relations */
        $relations = $entity['relations'];

        foreach ($relations as $relatedEntity) {
            $activity->addActivityTarget($relatedEntity);
        }

        $this->entityManager->flush();
    }
}
