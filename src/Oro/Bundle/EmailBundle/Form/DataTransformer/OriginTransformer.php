<?php

namespace Oro\Bundle\EmailBundle\Form\DataTransformer;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EmailBundle\Entity\EmailOrigin;
use Oro\Bundle\EmailBundle\Tools\EmailOriginHelper;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Exception\UnexpectedTypeException;

/**
 * Transforms between entity and id
 */
class OriginTransformer implements DataTransformerInterface
{
    /** @var EntityManager */
    protected $em;

    /** @var string */
    protected $className;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var EmailOriginHelper */
    protected $emailOriginHelper;

    /**
     * OriginTransformer constructor.
     *
     * @param EntityManager          $em
     * @param TokenAccessorInterface $tokenAccessor
     * @param EmailOriginHelper      $emailOriginHelper
     *
     * @throws UnexpectedTypeException When $queryBuilderCallback is set and not callable
     */
    public function __construct(
        EntityManager $em,
        TokenAccessorInterface $tokenAccessor,
        EmailOriginHelper $emailOriginHelper
    ) {
        $this->em = $em;
        $this->tokenAccessor = $tokenAccessor;
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
        list($id, $email) =  array_pad(explode('|', $value), 2, null);
        if (!$id) {
            $origin = $this->findByOwner($this->tokenAccessor->getUser());

            if (!$origin) {
                $origin = $this->createInternalOrigin($email);
            }

            return $origin;
        }

        return $this->loadEntityById($id);
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
        $result = $this->em->find($this->className, $id);
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
