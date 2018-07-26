<?php
declare(strict_types=1);
namespace Soatok\Website\Struct;

use ParagonIE\Stern\SternTrait;
use Soatok\Website\Engine\Cryptography\Password;
use Soatok\Website\Engine\Exceptions\{
    BaseException, NoSuchUserException, RaceConditionException, SecurityException
};
use Soatok\Website\Engine\{
    GlobalConfig,
    Policies\Unique,
    Struct
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
        'displayname' => 'displayName'
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

    /**
     * @return User
     * @throws BaseException
     * @throws SecurityException
     */
    public static function active(): self
    {
        if (isset($_SESSION['userid'])) {
            throw new SecurityException('Not logged in');
        }
        $user = User::strictById((int) $_SESSION['userid']);
        if (!($user instanceof User)) {
            throw new \TypeError();
        }
        return $user;
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
