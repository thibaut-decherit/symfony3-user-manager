<?php

namespace AppBundle\Model;

use AppBundle\Validator\Constraints as CustomAssert;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class AbstractUser
 * @package AppBundle\Model
 *
 * Abstract class for user classes requiring user management logic:
 *  - registration with activation link sent by email
 *  - login
 *  - username change
 *  - password change
 *  - email address change with verification link sent by email
 *  - password reset
 *
 * @UniqueEntity(
 *     fields={"username"},
 *     message="form_errors.user.unique_username",
 *     groups={"Account_Information", "Registration"}
 * )
 */
abstract class AbstractUser implements UserInterface, AdvancedUserInterface, EquatableInterface
{
    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255, unique=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Account_Information", "Registration"}
     * )
     * @Assert\Length(
     *     min=2,
     *     max=255,
     *     minMessage="form_errors.global.min_length",
     *     maxMessage="form_errors.global.max_length",
     *     groups={"Account_Information", "Registration"}
     * )
     * @Assert\Regex(
     *     pattern="/^[a-zA-Z0-9]*$/",
     *     message="form_errors.user.alphanumeric_only_username",
     *     groups={"Account_Information", "Registration"}
     * )
     */
    protected $username;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $password;

    /**
     * Used for model validation. Must not be persisted. Needed to avoid raw password overwriting
     * current user $user->getPassword() when being tested by UserPasswordValidator
     *
     * @var string|null
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Password_Change", "Registration"}
     * )
     * @Assert\Length(
     *     min=8,
     *     max=50,
     *     minMessage="form_errors.user.password_length",
     *     maxMessage="form_errors.user.password_length",
     *     groups={"Password_Change", "Registration"}
     * )
     * @CustomAssert\BreachedPassword(
     *     groups={"Password_Change", "Registration"}
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
     *     message="form_errors.global.not_blank",
     *     groups={"Registration"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.global.min_length",
     *      maxMessage = "form_errors.global.max_length",
     *      groups={"Registration"}
     * )
     * @Assert\Email(
     *      message = "form_errors.user.valid_email",
     *      checkMX = true,
     *      groups={"Registration"}
     * )
     */
    protected $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @Assert\NotBlank(
     *     message="form_errors.global.not_blank",
     *     groups={"Email_Change"}
     * )
     * @Assert\Length(
     *      min = 2,
     *      max = 255,
     *      minMessage = "form_errors.global.min_length",
     *      maxMessage = "form_errors.global.max_length",
     *      groups={"Email_Change"}
     * )
     * @Assert\Email(
     *      message = "form_errors.user.valid_email",
     *      checkMX = true,
     *      groups={"Email_Change"}
     * )
     */
    protected $emailChangePending;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $emailChangeToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $emailChangeRequestedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $accountDeletionToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $accountDeletionRequestedAt;

    /**
     * ORM mapping not needed if password hash algorithm generates it's own salt (e.g bcrypt)
     *
     * @var string
     *
     */
    protected $salt;

    /**
     * @var array
     *
     * @ORM\Column(type="json_array")
     */
    protected $roles;

    /**
     * @var DateTime
     *
     * @ORM\Column(type="datetime")
     */
    protected $registeredAt;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $activated;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=86, unique=true)
     */
    protected $activationToken;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=86, nullable=true, unique=true)
     */
    protected $passwordResetToken;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $passwordResetRequestedAt;

    public function __construct()
    {
        $this->roles = ['ROLE_USER'];
        $this->registeredAt = new DateTime();
        $this->activated = false;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return AbstractUser
     */
    public function setUsername(string $username): AbstractUser
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return AbstractUser
     */
    public function setPassword(string $password): AbstractUser
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    /**
     * @param string|null $plainPassword
     * @return AbstractUser
     */
    public function setPlainPassword(?string $plainPassword): AbstractUser
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return AbstractUser
     */
    public function setEmail(string $email): AbstractUser
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
     * @return AbstractUser
     */
    public function setEmailChangePending(?string $emailChangePending): AbstractUser
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
     * @return AbstractUser
     */
    public function setEmailChangeToken(?string $emailChangeToken): AbstractUser
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
     * @return AbstractUser
     */
    public function setEmailChangeRequestedAt(?DateTime $emailChangeRequestedAt): AbstractUser
    {
        $this->emailChangeRequestedAt = $emailChangeRequestedAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getAccountDeletionToken(): ?string
    {
        return $this->accountDeletionToken;
    }

    /**
     * @param string|null $accountDeletionToken
     * @return AbstractUser
     */
    public function setAccountDeletionToken(?string $accountDeletionToken): AbstractUser
    {
        $this->accountDeletionToken = $accountDeletionToken;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getAccountDeletionRequestedAt(): ?DateTime
    {
        return $this->accountDeletionRequestedAt;
    }

    /**
     * @param DateTime|null $accountDeletionRequestedAt
     * @return AbstractUser
     */
    public function setAccountDeletionRequestedAt(?DateTime $accountDeletionRequestedAt): AbstractUser
    {
        $this->accountDeletionRequestedAt = $accountDeletionRequestedAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return $this->salt;
    }

    /**
     * @param string|null $salt
     * @return AbstractUser
     */
    public function setSalt(?string $salt): AbstractUser
    {
        $this->salt = $salt;
        return $this;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     * @return AbstractUser
     */
    public function setRoles(array $roles): AbstractUser
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getRegisteredAt(): DateTime
    {
        return $this->registeredAt;
    }

    /**
     * @param DateTime $registeredAt
     * @return AbstractUser
     */
    public function setRegisteredAt(DateTime $registeredAt): AbstractUser
    {
        $this->registeredAt = $registeredAt;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActivated(): bool
    {
        return $this->activated;
    }

    /**
     * @param bool $activated
     * @return AbstractUser
     */
    public function setActivated(bool $activated): AbstractUser
    {
        $this->activated = $activated;
        return $this;
    }

    /**
     * @return string
     */
    public function getActivationToken(): string
    {
        return $this->activationToken;
    }

    /**
     * @param string $activationToken
     * @return AbstractUser
     */
    public function setActivationToken(string $activationToken): AbstractUser
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
     * @return AbstractUser
     */
    public function setPasswordResetToken(?string $passwordResetToken): AbstractUser
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
     * @return AbstractUser
     */
    public function setPasswordResetRequestedAt(?DateTime $passwordResetRequestedAt): AbstractUser
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
     * Checks if user has activated his account.
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
        if (!$user instanceof AbstractUser) {
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
     * @param int $accountDeletionTokenLifetime
     * @return bool
     */
    public function isAccountDeletionTokenExpired(int $accountDeletionTokenLifetime): bool
    {
        return $this->getAccountDeletionRequestedAt()->getTimestamp() + $accountDeletionTokenLifetime < time();
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
