<?php
declare(strict_types=1);
namespace Soatok\Website\Struct;

use Soatok\Website\Engine\Cryptography\Password;
use Soatok\Website\Engine\Exceptions\{
    BaseException,
    RaceConditionException
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
}
