<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Unit\Auth\Stores;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use T3chW1zard\EmporiaConnect\Auth\Stores\FileTokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\InMemoryTokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\Psr16TokenStore;
use T3chW1zard\EmporiaConnect\Auth\Stores\Psr6TokenStore;
use T3chW1zard\EmporiaConnect\Auth\TokenSet;
use T3chW1zard\EmporiaConnect\Contracts\TokenStoreContract;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class TokenStoresTest extends TestCase
{
    private static string $directory;

    public static function setUpBeforeClass(): void
    {
        self::$directory = sys_get_temp_dir().'/emporia-connect-tests-'.bin2hex(random_bytes(4));
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$directory.'/*') ?: [] as $file) {
            unlink($file);
        }

        if (is_dir(self::$directory)) {
            rmdir(self::$directory);
        }
    }

    /** @return iterable<string, array{\Closure(): TokenStoreContract}> */
    public static function stores(): iterable
    {
        yield 'in memory' => [static fn (): TokenStoreContract => new InMemoryTokenStore];
        yield 'psr-16' => [static fn (): TokenStoreContract => new Psr16TokenStore(new Psr16Cache(new ArrayAdapter))];
        yield 'psr-6' => [static fn (): TokenStoreContract => new Psr6TokenStore(new ArrayAdapter)];
        yield 'file' => [static fn (): TokenStoreContract => new FileTokenStore(self::$directory.'/'.bin2hex(random_bytes(4)).'.json')];
    }

    #[DataProvider('stores')]
    public function test_put_get_forget(\Closure $factory): void
    {
        $store = $factory();
        $tokens = new TokenSet('id', 'access', 'refresh', 1234);

        $this->assertNull($store->get());

        $store->put($tokens);
        $this->assertEquals($tokens, $store->get());

        $store->forget();
        $this->assertNull($store->get());
    }

    public function test_psr16_store_uses_key_and_ttl(): void
    {
        $cache = new Psr16Cache(new ArrayAdapter);
        $store = new Psr16TokenStore($cache, 'my-key', 60);

        $store->put(new TokenSet('id'));

        $this->assertIsArray($cache->get('my-key'));
    }

    public function test_file_store_writes_private_json(): void
    {
        $path = self::$directory.'/tokens.json';
        $store = new FileTokenStore($path);

        $store->put(new TokenSet('id', 'access', 'refresh', 99));

        $this->assertSame(['id_token' => 'id', 'access_token' => 'access', 'refresh_token' => 'refresh', 'expires_at' => 99], json_decode((string) file_get_contents($path), true));
        $this->assertSame('0600', substr(sprintf('%o', fileperms($path)), -4));
    }

    public function test_file_store_ignores_invalid_json(): void
    {
        $path = self::$directory.'/invalid.json';
        @mkdir(self::$directory, 0700, true);
        file_put_contents($path, 'not json');

        $this->assertNull((new FileTokenStore($path))->get());
    }
}
