<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Auth\Stores;

use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;
use T3chW1zard\EmporiaConnect\Exceptions\EmporiaException;

/**
 * Stores tokens in a JSON file, the PHP equivalent of PyEmVue's token_storage_file.
 * The file is written with 0600 permissions because it contains a refresh token.
 */
final readonly class FileTokenStore implements TokenStoreContract
{
    public function __construct(private string $path) {}

    public function get(): ?TokenSet
    {
        if (! is_file($this->path)) {
            return null;
        }

        $contents = file_get_contents($this->path);
        $data = $contents === false ? null : json_decode($contents, true);

        return is_array($data) ? TokenSet::fromArray($data) : null;
    }

    public function put(TokenSet $tokens): void
    {
        $directory = dirname($this->path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new EmporiaException("Unable to create token directory '{$directory}'.");
        }

        $temporary = $this->path.'.'.bin2hex(random_bytes(4)).'.tmp';

        if (file_put_contents($temporary, json_encode($tokens->toArray(), JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), LOCK_EX) === false) {
            throw new EmporiaException("Unable to write token file '{$this->path}'.");
        }

        chmod($temporary, 0600);

        if (! rename($temporary, $this->path)) {
            @unlink($temporary);

            throw new EmporiaException("Unable to write token file '{$this->path}'.");
        }
    }

    public function forget(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }
    }
}
