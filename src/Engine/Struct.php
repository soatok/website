<?php
declare(strict_types=1);
namespace Soatok\Website\Engine;

use Soatok\Website\Engine\Policies\Unique;
use ParagonIE\ConstantTime\Base64UrlSafe;
use ParagonIE\EasyDB\EasyDB;
use \ParagonIE_Sodium_Compat as NaCl;

/**
 * Class Struct
 * @package Soatok\Website\Engine
 */
abstract class Struct
{
    const TABLE_NAME = '';
    const PRIMARY_KEY = '';
    const DB_FIELD_NAMES = [];
    const BOOLEAN_FIELDS = [];

    /** @var EasyDB $db */
    protected $db;

    /** @var int $id */
    protected $id = 0;

    /** @var \DateTimeImmutable|null $created */
    protected $created = null;

    /** @var \DateTimeImmutable|null $modified */
    protected $modified = null;

    /** @var array<string, Struct> $objectCache */
    protected static $objectCache = [];

    /** @var string $runtimeCacheKey */
    protected static $runtimeCacheKey = '';

    /**
     * Struct constructor.
     *
     * @param EasyDB $db
     */
    public function __construct(EasyDB $db)
    {
        $this->db = $db;
    }

    /**
     * @param int|null $id
     * @return string
     * @throws \Error
     * @throws \SodiumException
     * @throws \TypeError
     */
    public function getCacheKey(?int $id = null): string
    {
        if (empty(static::$runtimeCacheKey)) {
            static::$runtimeCacheKey = \random_bytes(
                NaCl::CRYPTO_SHORTHASH_KEYBYTES
            );
        }

        $plaintext = \json_encode([
            'class' => \get_class($this),
            'id' => $id ?? $this->id
        ]);
        if (!\is_string($plaintext)) {
            throw new \Error('Could not calculate cache key');
        }
        return Base64UrlSafe::encode(
            NaCl::crypto_shorthash(
                $plaintext,
                static::$runtimeCacheKey
            )
        );
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function create(): bool
    {
        if ($this->id) {
            return $this->update();
        }
        $this->db->beginTransaction();

        /** @var array<string, mixed> $fields */
        $fields = [];
        /**
         * @var string $field
         * @var mixed $property
         */
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if (!\is_string($field)) {
                throw new \TypeError('Field name must be a string');
            }
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            $fields[$field] = $this->{$property};
        }
        $this->id = (int) $this->db->insertGet(
            (string) (static::TABLE_NAME),
            $fields,
            (string) (static::PRIMARY_KEY)
        );
        if ($this instanceof Unique) {
            self::$objectCache[$this->getCacheKey()] = $this;
        }
        return $this->db->commit();
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function update(): bool
    {
        if (!($this->id)) {
            return $this->create();
        }
        $this->db->beginTransaction();

        /** @var array<string, mixed> $fields */
        $fields = [];
        /**
         * @var string $field
         * @var mixed $property
         */
        foreach (static::DB_FIELD_NAMES as $field => $property) {
            if (!\is_string($field)) {
                throw new \TypeError('Field name must be a string');
            }
            if ($field === static::PRIMARY_KEY) {
                // No
                continue;
            }
            $fields[$field] = $this->{$property};
        }
        $this->db->update(
            (string) (static::TABLE_NAME),
            $fields,
            [static::PRIMARY_KEY => $this->id]
        );
        return $this->db->commit();
    }

    /**
     * Get the property from the object.
     *
     * @param string $name
     * @return mixed
     * @throws \InvalidArgumentException If the property does not exist.
     */
    public function __get(string $name)
    {
        if (!\property_exists($this, $name)) {
            throw new \InvalidArgumentException(
                'Property ' . $name . ' does not exist.'
            );
        }
        return $this->{$name};
    }

    /**
     * Strict-typed property setter.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws \TypeError
     */
    public function __set(string $name, $value)
    {
        if (!\property_exists($this, $name)) {
            throw new \InvalidArgumentException(
                'Property ' . $name . ' does not exist.'
            );
        }

        if ($name === 'id') {
            // RESERVED
            throw new \InvalidArgumentException(
                'Cannot override an object\'s primary key.'
            );
        }

        if (!\is_null($this->{$name})) {
            /* Enforce type strictness if only if property had a pre-established type. */
            $propType = Utility::getGenericType($this->{$name});
            $valueType = Utility::getGenericType($value);
            if ($propType !== $valueType) {
                throw new \TypeError(
                    'Property ' . $name .
                        ' expects type ' . $propType .
                        ', ' . $valueType . ' given.'
                );
            }
        }

        /** @psalm-suppress MixedAssignment */
        $this->{$name} = $value;
    }
}
