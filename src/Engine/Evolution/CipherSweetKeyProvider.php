<?php
declare(strict_types=1);
namespace Soatok\Website\Engine\Evolution;

use Soatok\Website\Engine\Cryptography\Key\SymmetricKey;
use Soatok\Website\Engine\Exceptions\BaseException;
use Soatok\Website\Engine\GlobalConfig;
use ParagonIE\CipherSweet\Contract\BackendInterface;
use ParagonIE\CipherSweet\Contract\KeyProviderInterface;
use ParagonIE\CipherSweet\Backend\Key\SymmetricKey as CipherSweetSymmetricKey;

/**
 * Class CipherSweetKeyProvider
 *
 * Provides an integration with CipherSweet, using the same symmetric key used
 * elsewhere in the application as the root key.
 *
 * @package Soatok\Website\Engine\Evolution
 */
class CipherSweetKeyProvider implements KeyProviderInterface
{
    /** @var BackendInterface $backend */
    private $backend;

    /** @var SymmetricKey $key */
    private $key;

    /**
     * CipherSweetKeyProvider constructor.
     *
     * @param BackendInterface $backend
     * @param SymmetricKey|null $key
     *
     * @throws BaseException
     */
    public function __construct(
        BackendInterface $backend,
        SymmetricKey $key = null
    ) {
        $this->backend = $backend;
        if (is_null($key)) {
            $key = (GlobalConfig::instance())->getSymmetricKey();
        }
        $this->key = $key;
    }

    /**
     * @return BackendInterface
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * @return CipherSweetSymmetricKey
     */
    public function getSymmetricKey(): CipherSweetSymmetricKey
    {
        return new CipherSweetSymmetricKey(
            $this->backend,
            $this->key->getRawKeyMaterial()
        );
    }
}
