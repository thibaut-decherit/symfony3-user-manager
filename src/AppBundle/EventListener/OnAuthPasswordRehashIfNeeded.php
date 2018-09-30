<?php

namespace AppBundle\EventListener;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Class OnAuthPasswordRehashIfNeeded
 * @package AppBundle\EventListener
 */
class OnAuthPasswordRehashIfNeeded
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    /**
     * @var int
     */
    private $cost;

    /**
     * OnAuthPasswordRehashIfNeeded constructor.
     * @param EntityManager $entityManager
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param int $cost
     */
    public function __construct(EntityManager $entityManager, UserPasswordEncoderInterface $passwordEncoder, int $cost)
    {
        $this->entityManager = $entityManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->cost = $cost;
    }

    /**
     * On authentication checks if user's password needs rehash in case of bcrypt cost change
     * WARNING : Will rehash password even if new cost is lower than current hash cost
     *
     * @param InteractiveLoginEvent $event
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $options = ["cost" => $this->cost];
        $currentHashedPassword = $user->getPassword();

        if (password_needs_rehash($currentHashedPassword, PASSWORD_BCRYPT, $options)) {
            $em = $this->entityManager;
            $plainPassword = $event->getRequest()->request->get('_password');

            $user->setPassword(
                $this->passwordEncoder->encodePassword($user, $plainPassword)
            );

            $em->persist($user);
            $em->flush();
        }
    }
}
