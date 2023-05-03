<?php

namespace Oro\Bundle\FormBundle\Provider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the route and route parameters of the save and return action
 */
class SaveAndReturnActionFormTemplateDataProvider implements FormTemplateDataProviderInterface
{
    private ?string $returnActionRoute = null;

    private ?array $returnActionRouteParameters = null;

    private ?string $returnActionRouteAclRole = null;

    private ?string $saveFormActionRoute = null;

    private ?array $saveFormActionRouteParameters = null;

    public function setReturnActionRoute(
        string $returnActionRoute,
        array $returnActionRouteParameters,
        string $returnActionRouteAclRole
    ): self {
        $this->returnActionRoute = $returnActionRoute;
        $this->returnActionRouteParameters = $returnActionRouteParameters;
        $this->returnActionRouteAclRole = $returnActionRouteAclRole;

        return $this;
    }

    public function setSaveFormActionRoute(string $saveFormActionRoute, array $saveFormActionRouteParameters): self
    {
        $this->saveFormActionRoute = $saveFormActionRoute;
        $this->saveFormActionRouteParameters = $saveFormActionRouteParameters;

        return $this;
    }

    public function getData($entity, FormInterface $form, Request $request)
    {
        $data = [
            'entity' => $entity,
            'form' => $form->createView(),
        ];

        if ($this->returnActionRoute && $this->returnActionRouteParameters) {
            $data = array_merge(
                $data,
                [
                    'returnAction' => [
                        'route' => $this->returnActionRoute,
                        'parameters' => $this->returnActionRouteParameters,
                        'aclRole' => $this->returnActionRouteAclRole,
                    ]
                ]
            );
        }

        if ($this->saveFormActionRoute && $this->saveFormActionRouteParameters) {
            $data = array_merge(
                $data,
                [
                    'saveFormAction' => [
                        'route' => $this->saveFormActionRoute,
                        'parameters' => $this->saveFormActionRouteParameters,
                    ]
                ]
            );
        }

        return $data;
    }
}
