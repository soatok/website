<?php
declare(strict_types=1);
namespace Soatok\Website\Struct;

use Kelunik\TwoFactor\Oath;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\Stern\SternTrait;
use Soatok\Website\Engine\Cryptography\Password;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    NoSuchUserException,
    RaceConditionException,
    SecurityException
};
use Soatok\Website\Engine\{
    Cryptography\Symmetric, GlobalConfig, Policies\Unique, Struct
};
use ParagonIE\HiddenString\HiddenString;
use ParagonIE_Sodium_Core_Util as Util;

/**
 * Class User
 * @package Soatok\Website\Struct
 */
class User extends Struct implements Unique
{
    use SternTrait;

    const TABLE_NAME = 'website_users';
    const PRIMARY_KEY = 'userid';
    const DB_FIELD_NAMES = [
        'userid' => 'id',
        'active' => 'active',
        'username' => 'username',
        'pwhash' => 'pwHash',
        'email' => 'email',
        'displayname' => 'displayName',
        'twofactor' => 'twoFactorSecret',
        'gpgfingerprint' => 'gpgFingerprint'
    ];

    /** @var bool $active */
    protected $active = false;

    /** @var string $email */
    protected $email = '';

    /** @var string $displayName */
    protected $displayName = '';

    /** @var string $username */
    protected $username = '';

    /** @var string $pwHash */
    protected $pwHash = '';

    /** @var string $twoFactorSecret */
    protected $twoFactorSecret = '';

    /** @var string $gpgFingerprint */
    protected $gpgFingerprint = '';

    /**
     * @return User
     * @throws BaseException
     * @throws SecurityException
     */
    public static function active(): self
    {
        if (!isset($_SESSION['userid'])) {
            throw new SecurityException('Not logged in');
        }
        $user = User::strictById((int) $_SESSION['userid']);
        if (!($user instanceof User)) {
            throw new \TypeError();
        }
        return $user;
    }

    /**
     * @return HiddenString
     * @throws BaseException
     * @throws \SodiumException
     */
    public function createAuthToken(): HiddenString
    {
        $this->db->beginTransaction();
        $selector = Base64UrlSafe::encode(\random_bytes(18));
        $validator = Base64UrlSafe::encode(\random_bytes(33));
        $mac = Symmetric::auth(
            $selector . ':' . $validator,
            GlobalConfig::instance()->getSymmetricKey()
        );
        $this->db->insert(
            'website_token_remember',
            [
                'userid' => $this->id,
                'selector' => $selector,
                'validator' => $mac
            ]
        );
        if (!$this->db->commit()) {
            throw new SecurityException('Could not save long-term token');
        }
        return new HiddenString($selector . ':' . $validator);
    }

    /**
     * @return HiddenString
     * @throws BaseException
     * @throws \SodiumException
     */
    public function createRecoveryToken(): HiddenString
    {
        $this->db->beginTransaction();
        $selector = Base64UrlSafe::encode(\random_bytes(18));
        $validator = Base64UrlSafe::encode(\random_bytes(33));
        $mac = Symmetric::auth(
            $selector . ':' . $validator,
            GlobalConfig::instance()->getSymmetricKey()
        );
        $this->db->insert(
            'website_token_recovery',
            [
                'userid' => $this->id,
                'selector' => $selector,
                'validator' => $mac
            ]
        );
        if (!$this->db->commit()) {
            throw new SecurityException('Could not save long-term token');
        }
        return new HiddenString($selector . ':' . $validator);
    }

    /**
     * @param string $username
     *
     * @return bool
     * @throws BaseException
     */
    public static function usernameIsTaken(string $username): bool
    {
        return GlobalConfig::instance()->getDatabase()->exists(
            "SELECT count(*) FROM " . static::TABLE_NAME . " WHERE username = ?",
            $username
        );
    }

    /**
     * @param HiddenString $password
     *
     * @return bool
     * @throws BaseException
     * @throws \SodiumException
     */
    public function checkPassword(HiddenString $password): bool
    {
        if (!$this->id) {
            throw new RaceConditionException(
                'You cannot set a password until the user record has been saved ' .
                'to the database, in order to prevent race conditions against ' .
                'the sequential primary key.'
            );
        }

        return $this->getPasswordStorage()->verify(
            $password,
            $this->pwHash,
            Util::store64_le($this->id)
        );
    }

    /**
     * @param string $authCode
     *
     * @return bool
     * @throws BaseException
     * @throws \SodiumException
     */
    public function checkSecondFactor(string $authCode): bool
    {
        if (!\is_string($this->twoFactorSecret)) {
            return false;
        }
        return (new Oath())->verifyTotp(
            Symmetric::decryptWithAd(
                $this->twoFactorSecret,
                (GlobalConfig::instance())->getSymmetricKey(),
                Util::store64_le($this->id)
            )->getString(),
            $authCode
        );
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return (string) $this->email;
    }

    /**
     * @return string
     */
    public function getGPGFingerprint(): string
    {
        return (string) $this->gpgFingerprint;
    }

    /**
     * @return Password
     * @throws BaseException
     */
    protected function getPasswordStorage(): Password
    {
        return new Password(
            (GlobalConfig::instance())->getSymmetricKey()
        );
    }

    /**
     * @param HiddenString $password
     *
     * @return User
     * @throws BaseException
     * @throws \SodiumException
     */
    public function setPassword(HiddenString $password): self
    {
        if (!$this->id) {
            throw new RaceConditionException(
                'You cannot set a password until the user record has been saved ' .
                'to the database, in order to prevent race conditions against ' .
                'the sequential primary key.'
            );
        }

        $this->pwHash = $this->getPasswordStorage()->hash(
            $password,
            Util::store64_le($this->id)
        );
        return $this;
    }

    /**
     * @param HiddenString $secret
     *
     * @return User
     * @throws BaseException
     * @throws RaceConditionException
     * @throws \SodiumException
     */
    public function setTwoFactorSecret(HiddenString $secret): self
    {
        if (!$this->id) {
            throw new RaceConditionException(
                'You cannot set the two-factor secret until the user record has ' .
                'been saved to the database, in order to prevent race conditions ' .
                'against the sequential primary key.'
            );
        }
        $this->twoFactorSecret = Symmetric::encryptWithAd(
            $secret,
            (GlobalConfig::instance())->getSymmetricKey(),
            Util::store64_le($this->id)
        );

        return $this;
    }

    /**
     * @param string $property
     * @param bool|int|string|float|null $value
     * @return self
     */
    public function set(string $property, $value): self
    {
        $this->__set($property, $value);
        return $this;
    }

    /**
     * @param HiddenString $token
     *
     * @return User
     * @throws BaseException
     * @throws \SodiumException
     * @throws NoSuchUserException
     */
    public static function byAuthToken(HiddenString $token): self
    {
        $db = GlobalConfig::instance()->getDatabase();
        list($selector, $validator) = \explode(':', $token->getString());
        $mac = Symmetric::auth(
            $selector . ':' . $validator,
            GlobalConfig::instance()->getSymmetricKey()
        );

        $db->beginTransaction();
        $matches = $db->run(
            "SELECT * FROM website_token_remember WHERE selector = ?",
            $selector
        );
        foreach ($matches as $row) {
            if (\hash_equals($mac, $row['validator'])) {
                $db->delete(
                    'website_token_remember',
                    [
                        'tokenid' => $row['tokenid']
                    ]
                );
                $db->commit();
                return User::byId($row[static::PRIMARY_KEY]);
            }
        }
        $db->rollBack();
        throw new NoSuchUserException();
    }

    /**
     * @param HiddenString $token
     *
     * @return User
     * @throws BaseException
     * @throws \SodiumException
     * @throws NoSuchUserException
     */
    public static function byRecoveryToken(HiddenString $token): self
    {
        $db = GlobalConfig::instance()->getDatabase();
        list($selector, $validator) = \explode(':', $token->getString());
        $mac = Symmetric::auth(
            $selector . ':' . $validator,
            GlobalConfig::instance()->getSymmetricKey()
        );

        $db->beginTransaction();
        $matches = $db->run(
            "SELECT * FROM website_token_recovery WHERE selector = ?",
            $selector
        );
        foreach ($matches as $row) {
            if (\hash_equals($mac, $row['validator'])) {
                $db->delete(
                    'website_token_recovery',
                    [
                        'tokenid' => $row['tokenid']
                    ]
                );
                $db->commit();
                return User::byId($row[static::PRIMARY_KEY]);
            }
        }
        $db->rollBack();
        throw new NoSuchUserException();
    }

    /**
     * @param int $userId
     *
     * @return self
     * @throws BaseException
     */
    public static function byId(int $userId): self
    {
        $user = parent::strictById($userId);
        if (!($user instanceof User)) {
            throw new \TypeError();
        }
        return $user;
    }


    /**
     * @param string $username
     *
     * @return User
     * @throws BaseException
     * @throws NoSuchUserException
     */
    public static function byUsername(string $username): self
    {
        $userId = GlobalConfig::instance()->getDatabase()->cell(
            "SELECT " . static::PRIMARY_KEY . " FROM " . static::TABLE_NAME . " WHERE username = ?",
            $username
        );
        if (!$userId) {
            throw new NoSuchUserException('Invalid username and/or passphrase');
        }
        return self::byId($userId);
    }
}
