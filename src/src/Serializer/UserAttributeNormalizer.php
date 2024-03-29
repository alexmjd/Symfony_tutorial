<?php

namespace App\Serializer;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\Normalizer\ContextAwareNormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;


/**
 * Create a custom normalizer to return current user his full informations
 */
class UserAttributeNormalizer implements ContextAwareNormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    const USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED = 'USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED';

    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    public function supportsNormalization(
        $data, 
        ?string $format = null, 
        array $context = [])
    {
        if (isset($context[self::USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED]))
            return false;

        return $data instanceof User;
    }   

    public function normalize(
        $object, 
        ?string $format = null, 
        array $context = [])
    {
        if ($this->isUserHimself($object))
            $context['groups'][] = 'get-owner';

        return $this->passOn($object, $format, $context);
    }

    private function isUserHimself($object)
    {
        return $object->getUsername() === $this->tokenStorage->getToken()->getUsername();
    }

    private function passOn($object, $format, $context)
    {
        if (!$this->serializer instanceof NormalizerInterface)
            throw new \LogicException(sprintf('Cannot normalize object %s because the injected serializer is not a normalizer.', $object));

        $context[self::USER_ATTRIBUTE_NORMALIZER_ALREADY_CALLED] = true;

        return $this->serializer->normalize($object, $format, $context);
    }

    // public function setSerializer(SerializerInterface $serializer)
    // {
        
    // }
}
