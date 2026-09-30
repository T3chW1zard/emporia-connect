<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Tests\Integration\Symfony;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use T3chW1zard\EmporiaConnect\Client;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\Symfony\EmporiaConnectBundle;
use T3chW1zard\EmporiaConnect\Tests\Support\MockHttp;
use T3chW1zard\EmporiaConnect\Tests\TestCase;

final class EmporiaConnectBundleTest extends TestCase
{
    /**
     * @param  array<string, mixed>  $config
     */
    private function container(array $config, ?callable $configure = null): ContainerBuilder
    {
        $container = new ContainerBuilder;
        $container->setParameter('kernel.debug', false);
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        $extension = (new EmporiaConnectBundle)->getContainerExtension();
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
        $this->assertSame('emporia_connect', $extension->getAlias());

        $extension->load([$config], $container);

        if ($configure !== null) {
            $configure($container);
        }

        $container->compile();

        return $container;
    }

    public function test_it_registers_the_client_service(): void
    {
        $container = $this->container(['username' => 'user@example.com', 'password' => 'secret'], static function (ContainerBuilder $container): void {
            $container->register('cache.app', ArrayAdapter::class);
        });

        $client = $container->get(ClientContract::class);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame($client, $container->get('emporia_connect.client'));
    }

    public function test_it_works_without_the_cache_service(): void
    {
        $container = $this->container(['username' => 'user@example.com', 'password' => 'secret']);

        $this->assertInstanceOf(ClientContract::class, $container->get(ClientContract::class));
    }

    public function test_it_uses_a_custom_http_client_and_tokens(): void
    {
        $http = (new MockHttp)->json(['customerGid' => 5]);

        $container = $this->container(['id_token' => 'symfony-token', 'cache' => null, 'http_client' => 'my.http'], static function (ContainerBuilder $container): void {
            $container->register('my.http')->setSynthetic(true)->setPublic(true);
        });
        $container->set('my.http', $http->client);

        $this->assertSame(5, $container->get(ClientContract::class)->customers()->me()->customerGid);
        $this->assertSame('symfony-token', $http->request(0)->getHeaderLine('authtoken'));
    }

    public function test_container_can_be_dumped_and_loaded(): void
    {
        $container = $this->container(['username' => 'user@example.com', 'password' => 'secret'], static function (ContainerBuilder $container): void {
            $container->register('cache.app', ArrayAdapter::class);
        });

        $class = 'EmporiaDumpedContainer'.bin2hex(random_bytes(4));
        eval('?>'.(new PhpDumper($container))->dump(['class' => $class]));

        $this->assertInstanceOf(Client::class, (new $class)->get(ClientContract::class));
    }

    public function test_invalid_auth_flow_is_rejected(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->container(['auth_flow' => 'magic']);
    }
}
