<?php

namespace Oro\Bundle\FormBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handler for the backoffice form with dynamic structure based on ajax requests
 */
class FormWithAjaxReloadHandler implements FormHandlerInterface
{
    use RequestHandlerTrait;

    public const WITHOUT_SAVING_KEY = 'reloadWithoutSaving';

    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function process($data, FormInterface $form, Request $request): bool
    {
        $form->setData($data);

        if ($this->isApplicable($request)) {
            $this->submitPostPutRequest($form, $request);

            if (!is_null($request->get(self::WITHOUT_SAVING_KEY))) {
                $form->clearErrors(true);
            }

            if ($form->isValid() && $request->get(self::WITHOUT_SAVING_KEY) === null) {
                $manager = $this->doctrine->getManagerForClass($data::class);
                $manager->persist($data);
                $manager->flush();

                return true;
            }
        }

        return false;
    }

    private function isApplicable(Request $request): bool
    {
        $methods = [Request::METHOD_POST, Request::METHOD_PUT];

        if (!in_array($request->getMethod(), $methods, true)) {
            return false;
        }

        return true;
    }
}
