<?php

namespace AppBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Validator\Constraints as CustomAssert;

/**
 * User
 *
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 *
 * @UniqueEntity(
 *     fields={"username"},
 *     message="form_errors.unique_username",
 *     groups={"Registration", "User_Information"}
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
     *     groups={"Registration", "User_Information"}
     * )
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     minMessage="form_errors.min_length",
     *     maxMessage="form_errors.max_length",
     *     groups={"Registration", "User_Information"}
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9]*$/",
     *     message="form_errors.alphanumeric_only_username",
     *     groups={"Registration", "User_Information"}
     * )
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * Used for model validation. Must not be persisted. Needed to avoid raw password overwriting
     * current user $user->getPassword() when being tested by UserPasswordValidator
     *
     * @var null|string
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *     groups={"Registration", "Password_Change"}
     * )
     * @Assert\Length(
     *     min=8,
     *     max=150,
     *     minMessage="form_errors.password_length",
     *     maxMessage="form_errors.password_length",
     *     groups={"Registration", "Password_Change"}
     * )
     * @CustomAssert\BreachedPassword(
     *     groups={"Registration", "Password_Change"}
     * )
     *
     */
    protected $plainPassword;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *     groups={"Registration"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"Registration"}
     * )
     * @Assert\Email(
     *      message = "form_errors.valid_email",
     *      checkMX = true,
     *      groups={"Registration"}
     * )
     */
    private $email;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.not_blank",
     *     groups={"Email_Change"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.min_length",
     *      maxMessage = "form_errors.max_length",
     *      groups={"Email_Change"}
     * )
     * @Assert\Email(
     *      message = "form_errors.valid_email",
     *      checkMX = true,
     *      groups={"Email_Change"}
     * )
     */
    private $emailChangePending;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    private $emailChangeToken;

    /**
     * @var null|DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $emailChangeRequestedAt;

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
     * @ORM\Column(type="json_array")
     */
    private $roles;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $registeredAt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $activated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=86, unique=true)
     */
    private $activationToken;

    /**
     * @var null|string
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    private $passwordResetToken;

    /**
     * @var null|DateTime
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $passwordResetRequestedAt;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->registeredAt = new DateTime();
        $this->activated = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return User
     */
    public function setId(int $id): User
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return User
     */
    public function setUsername(string $username): User
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return User
     */
    public function setPassword(string $password): User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     * @return User
     */
    public function setPlainPassword(?string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return User
     */
    public function setEmail(string $email): User
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailChangePending(): ?string
    {
        return $this->emailChangePending;
    }

    /**
     * @param string|null $emailChangePending
     * @return User
     */
    public function setEmailChangePending(?string $emailChangePending): User
    {
        $this->emailChangePending = $emailChangePending;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmailChangeToken(): ?string
    {
        return $this->emailChangeToken;
    }

    /**
     * @param string|null $emailChangeToken
     * @return User
     */
    public function setEmailChangeToken(?string $emailChangeToken): User
    {
        $this->emailChangeToken = $emailChangeToken;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getEmailChangeRequestedAt(): ?DateTime
    {
        return $this->emailChangeRequestedAt;
    }

    /**
     * @param DateTime|null $emailChangeRequestedAt
     * @return User
     */
    public function setEmailChangeRequestedAt(?DateTime $emailChangeRequestedAt): User
    {
        $this->emailChangeRequestedAt = $emailChangeRequestedAt;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string $salt
     * @return User
     */
    public function setSalt(string $salt): User
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getRoles(): ?array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles): User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getRegisteredAt(): ?DateTime
    {
        return $this->registeredAt;
    }

    /**
     * @param DateTime $registeredAt
     * @return User
     */
    public function setRegisteredAt(DateTime $registeredAt): User
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     * @return User
     */
    public function setActivated(bool $activated): User
    {
        $this->activated = $activated;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getActivationToken(): ?string
    {
        return $this->activationToken;
    }

    /**
     * @param string $activationToken
     * @return User
     */
    public function setActivationToken(string $activationToken): User
    {
        $this->activationToken = $activationToken;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPasswordResetToken(): ?string
    {
        return $this->passwordResetToken;
    }

    /**
     * @param string|null $passwordResetToken
     * @return User
     */
    public function setPasswordResetToken(?string $passwordResetToken): User
    {
        $this->passwordResetToken = $passwordResetToken;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getPasswordResetRequestedAt(): ?DateTime
    {
        return $this->passwordResetRequestedAt;
    }

    /**
     * @param DateTime|null $passwordResetRequestedAt
     * @return User
     */
    public function setPasswordResetRequestedAt(?DateTime $passwordResetRequestedAt): User
    {
        $this->passwordResetRequestedAt = $passwordResetRequestedAt;
        return $this;
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
    public function isAccountNonExpired(): bool
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
    public function isAccountNonLocked(): bool
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
    public function isCredentialsNonExpired(): bool
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
    public function isEnabled(): bool
    {
        return $this->activated;
    }

    /**
     * @param UserInterface $user
     * @return bool
     */
    public function isEqualTo(UserInterface $user): bool
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
     * Generates an URI safe base64 encoded string that does not contain "+", "/" or "=" which need to be URL
     * encoded and make URLs unnecessarily longer.
     * With 512 bits of entropy this method will return a string of 86 characters, with 256 bits of entropy it will
     * return 43 characters, and so on.
     *
     * @param int $entropy
     * @return string
     * @throws Exception
     */
    public function generateSecureToken(int $entropy = 512): string
    {
        $bytes = random_bytes($entropy / 8);

        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /**
     * @param int $emailChangeRequestRetryDelay
     * @return bool
     */
    public function isEmailChangeRequestRetryDelayExpired(int $emailChangeRequestRetryDelay): bool
    {
        return $this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeRequestRetryDelay < time();
    }

    /**
     * @param int $emailChangeTokenLifetime
     * @return bool
     */
    public function isEmailChangeTokenExpired(int $emailChangeTokenLifetime): bool
    {
        return $this->getEmailChangeRequestedAt()->getTimestamp() + $emailChangeTokenLifetime < time();
    }

    /**
     * @param int $passwordResetRequestRetryDelay
     * @return bool
     */
    public function isPasswordResetRequestRetryDelayExpired(int $passwordResetRequestRetryDelay): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetRequestRetryDelay < time();
    }

    /**
     * @param int $passwordResetTokenLifetime
     * @return bool
     */
    public function isPasswordResetTokenExpired(int $passwordResetTokenLifetime): bool
    {
        return $this->getPasswordResetRequestedAt()->getTimestamp() + $passwordResetTokenLifetime < time();
    }
}
