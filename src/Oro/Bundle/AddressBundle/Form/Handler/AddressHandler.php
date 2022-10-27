<?php

namespace Oro\Bundle\AddressBundle\Form\Handler;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for Address form.
 */
class AddressHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    protected ObjectManager $manager;

    public function __construct(ObjectManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritDoc}
     */
    public function process($entity, FormInterface $form, Request $request)
    {
        $form->setData($entity);
        if (\in_array($request->getMethod(), ['POST', 'PUT'], true)) {
            $this->submitPostPutRequest($form, $request);
            if ($form->isValid()) {
                $this->onSuccess($entity);

                return true;
            }
        }

        return false;
    }

    /**
     * "Success" form handler
     */
    protected function onSuccess(AbstractAddress $address): void
    {
        $this->manager->persist($address);
        $this->manager->flush();
    }
}
