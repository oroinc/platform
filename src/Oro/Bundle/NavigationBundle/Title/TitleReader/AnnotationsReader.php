<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Doctrine\Common\Annotations\Reader;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\NavigationBundle\Annotation\TitleTemplate;

class AnnotationsReader implements ReaderInterface
{
    /** @var RequestStack */
    private $requestStack;

    /** @var Reader */
    private $reader;

    /**
     * @param RequestStack $requestStack
     * @param Reader $reader
     */
    public function __construct(RequestStack $requestStack, Reader $reader)
    {
        $this->requestStack = $requestStack;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle($route)
    {
        $request = $this->requestStack->getCurrentRequest();
        $controller = $request->get('_controller');
        if (strpos($controller, '::')) {
            list($class, $method) = explode('::', $controller);

            $reflectionMethod = new \ReflectionMethod($class, $method);

            $annotation = $this->reader->getMethodAnnotation($reflectionMethod, TitleTemplate::class);
            if ($annotation) {
                return $annotation->getValue();
            }
        }

        return null;
    }
}
