<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;

/**
 * Transforms between entity and id
 */
class OriginTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $className;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /**
     * OriginTransformer constructor.
     *
     * @param EntityManager $em
     * @param SecurityFacade $securityFacade
     * @param EmailOriginHelper $emailOriginHelper
     *
     * @throws UnexpectedTypeException When $queryBuilderCallback is set and not callable
     */
    public function __construct(
        EntityManager $em,
        SecurityFacade $securityFacade,
        EmailOriginHelper $emailOriginHelper
    ) {
        $this->em = $em;
        $this->securityFacade = $securityFacade;
        $this->emailOriginHelper = $emailOriginHelper;
        $this->className = EmailOrigin::class;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        if (!is_object($value)) {
            throw new UnexpectedTypeException($value, 'object');
        }

        return $value->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return null;
        }
        $values =  explode('|', $value);
        if (!array_key_exists(0, $values) || !$values[0]) {
            $origin = $this->findByOwner($this->securityFacade->getLoggedUser());

            if (!$origin) {
                $origin = $this->createInternalOrigin($values[1]);
            }

            return $origin;
        }

        return $this->loadEntityById($value);
    }

    /**
     * Load entity by id
     *
     * @param mixed $id
     * @return object
     * @throws UnexpectedTypeException if query builder callback returns invalid type
     * @throws TransformationFailedException if value not matched given $id
     */
    protected function loadEntityById($id)
    {
        $repository = $this->em->getRepository($this->className);
        $result = $repository->find($id);
        if (!$result) {
            throw new TransformationFailedException(sprintf('The value "%s" does not exist or not unique.', $id));
        }

        return $result;
    }

    /**
     * @param $owner
     *
     * @return null|object
     */
    protected function findByOwner($owner)
    {
        $repository = $this->em->getRepository($this->className);

        return $repository->findOneBy(['owner'=> $owner]);
    }

    /**
     * @param string $email
     *
     * @return EmailOrigin
     */
    protected function createInternalOrigin($email)
    {
        return $this->emailOriginHelper->getEmailOrigin($email);
    }
}
