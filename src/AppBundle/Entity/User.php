<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 *
 * @UniqueEntity(
 *     fields={"email"},
 *     message="form_errors.unique_email",
 *     groups={"registration", "user_information"}
 * )
 * @UniqueEntity(
 *     fields={"username"},
 *     message="form_errors.unique_username",
 *     groups={"registration", "user_information"}
 * )
 */
class User implements UserInterface, AdvancedUserInterface, EquatableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *      groups={"registration", "user_information"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"registration", "user_information"}
     * )
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     *
     * @Assert\Regex(
     *     pattern = "/^(?=\D*\d)(?=[^a-z]*[a-z])(?=[^A-Z]*[A-Z])[\w~@#$%^&*+=`|{}:;!.?""''()\[\]-]{8,50}$/",
     *     message = "form_errors.password_strength",
     *     groups={"registration"}
     * )
     *
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *      groups={"registration", "user_information"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"registration", "user_information"}
     * )
     * @Assert\Email(
     *      message = "form_errors.valid_email",
     *      checkMX = true,
     *      groups={"registration", "user_information"}
     * )
     */
    private $email;

    /**
     * ORM mapping not needed if password hash algorithm generates it's own salt (e.g bcrypt)
     *
     * @var string
     *
     */
    private $salt;

    /**
     * @var array
     *
     * @ORM\Column(type="array", nullable=true)
     */
    private $roles;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $hasBeenActivated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $activationToken;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $passwordResetToken;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordResetRequestedAt;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->hasBeenActivated = false;
        $this->activationToken = $this->generateSecureToken();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set username
     *
     * @param string $username
     *
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set roles
     *
     * @param array $roles
     *
     * @return User
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * Get roles
     *
     * @return array
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * Set hasBeenActivated
     *
     * @param bool $hasBeenActivated
     *
     * @return User
     */
    public function setHasBeenActivated($hasBeenActivated)
    {
        $this->hasBeenActivated = $hasBeenActivated;

        return $this;
    }

    /**
     * Get hasBeenActivated
     *
     * @return bool
     */
    public function getHasBeenActivated()
    {
        return $this->hasBeenActivated;
    }

    /**
     * Set activationToken
     *
     * @param string $activationToken
     *
     * @return User
     */
    public function setActivationToken($activationToken)
    {
        $this->activationToken = $activationToken;

        return $this;
    }

    /**
     * Get activationToken
     *
     * @return string
     */
    public function getActivationToken()
    {
        return $this->activationToken;
    }

    /**
     * Set passwordResetToken
     *
     * @param string $passwordResetToken
     *
     * @return User
     */
    public function setPasswordResetToken($passwordResetToken)
    {
        $this->passwordResetToken = $passwordResetToken;

        return $this;
    }

    /**
     * Get passwordResetToken
     *
     * @return string
     */
    public function getPasswordResetToken()
    {
        return $this->passwordResetToken;
    }

    /**
     * Set passwordResetRequestedAt
     *
     * @param string $passwordResetRequestedAt
     *
     * @return User
     */
    public function setPasswordResetRequestedAt($passwordResetRequestedAt)
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;

        return $this;
    }

    /**
     * Get passwordResetRequestedAt
     *
     * @return \DateTime
     */
    public function getPasswordResetRequestedAt()
    {
        return $this->passwordResetRequestedAt;
    }

    public function eraseCredentials()
    {
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user has activated his/her account.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->hasBeenActivated;
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof User) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->salt !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function generateSecureToken()
    {
        return sha1(random_bytes(256));
    }

    /**
     * @param int $passwordResetRequestRetryDelay
     * @return bool
     */
    public function isPasswordResetRequestRetryDelayExpired(int $passwordResetRequestRetryDelay)
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetRequestRetryDelay < time();
    }

    /**
     * @param int $passwordResetTokenLifetime
     * @return bool
     */
    public function isPasswordResetTokenExpired(int $passwordResetTokenLifetime)
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetTokenLifetime < time();
    }
}
