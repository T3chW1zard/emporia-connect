<?php

declare(strict_types=1);

namespace T3chW1zard\EmporiaConnect\Symfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use T3chW1zard\EmporiaConnect\Contracts\ClientContract;
use T3chW1zard\EmporiaConnect\EmporiaConnect;

/**
 * Symfony bundle registering the Emporia client as the ClientContract service.
 *
 *   # config/packages/emporia_connect.yaml
 *   emporia_connect:
 *       username: '%env(EMPORIA_USERNAME)%'
 *       password: '%env(EMPORIA_PASSWORD)%'
 *       cache: cache.app
 */
final class EmporiaConnectBundle extends AbstractBundle
{
    protected string $extensionAlias = 'emporia_connect';

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
            ->scalarNode('username')->defaultNull()->info('Emporia account email address.')->end()
            ->scalarNode('password')->defaultNull()->info('Emporia account password.')->end()
            ->scalarNode('id_token')->defaultNull()->end()
            ->scalarNode('access_token')->defaultNull()->end()
            ->scalarNode('refresh_token')->defaultNull()->end()
            ->scalarNode('token_file')->defaultNull()->info('Store tokens in this JSON file instead of a cache.')->end()
            ->scalarNode('cache')->defaultValue('cache.app')->info('Service id of a PSR-6 or PSR-16 cache used to keep tokens. Set to null to disable.')->end()
            ->scalarNode('cache_key')->defaultNull()->end()
            ->integerNode('cache_ttl')->defaultValue(2592000)->end()
            ->scalarNode('http_client')->defaultNull()->info('Service id of a PSR-18 client. Defaults to Guzzle.')->end()
            ->enumNode('auth_flow')->values(['srp', 'password'])->defaultValue('srp')->end()
            ->floatNode('connect_timeout')->defaultValue(6.03)->end()
            ->floatNode('read_timeout')->defaultValue(10.03)->end()
            ->integerNode('token_refresh_leeway')->defaultValue(60)->end()
            ->arrayNode('retry')
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('max_attempts')->defaultValue(5)->end()
            ->integerNode('initial_delay_ms')->defaultValue(500)->end()
            ->integerNode('max_delay_ms')->defaultValue(30000)->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $cache = is_string($config['cache'] ?? null) && $config['cache'] !== ''
            ? new Reference($config['cache'], ContainerInterface::NULL_ON_INVALID_REFERENCE)
            : null;
        $httpClient = is_string($config['http_client'] ?? null) && $config['http_client'] !== ''
            ? new Reference($config['http_client'])
            : null;

        unset($config['cache'], $config['http_client']);

        $container->services()
            ->set('emporia_connect.client', ClientContract::class)
            ->factory([EmporiaConnect::class, 'fromOptions'])
            ->args([$config, $cache, $httpClient])
            ->public()
            ->alias(ClientContract::class, 'emporia_connect.client')
            ->public();
    }
}
