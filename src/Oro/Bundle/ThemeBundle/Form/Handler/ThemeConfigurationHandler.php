<?php

namespace Oro\Bundle\ThemeBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\ThemeBundle\Entity\ThemeConfiguration;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * The handler for theme configuration form.
 */
class ThemeConfigurationHandler implements FormHandlerInterface
{
    use  RequestHandlerTrait;

    public const WITHOUT_SAVING_KEY = 'reloadWithoutSaving';

    public function __construct(
        private ManagerRegistry $registry
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function process($data, FormInterface $form, Request $request): bool
    {
        if (!$data instanceof ThemeConfiguration) {
            throw new \InvalidArgumentException('Argument data should be instance of ThemeConfiguration entity');
        }

        if ($this->isApplicable($request)) {
            $this->submitPostPutRequest($form, $request);

            if ($form->isValid()) {
                $manager = $this->registry->getManagerForClass(ThemeConfiguration::class);
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

        return in_array($request->getMethod(), $methods, true) && $request->get(self::WITHOUT_SAVING_KEY) === null;
    }
}
