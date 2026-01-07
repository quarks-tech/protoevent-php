<?php

declare(strict_types=1);

namespace QuarksTech\ProtoEvent\SymfonyBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('quarks_tech_proto_event');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('connection')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('dsn')
                            ->defaultNull()
                            ->info('DSN string (takes precedence over individual settings)')
                        ->end()
                        ->scalarNode('host')->defaultValue('localhost')->end()
                        ->integerNode('port')->defaultValue(5672)->end()
                        ->scalarNode('user')->defaultValue('guest')->end()
                        ->scalarNode('password')->defaultValue('guest')->end()
                        ->scalarNode('vhost')->defaultValue('/')->end()
                    ->end()
                ->end()
                ->arrayNode('publisher')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('delivery_mode')
                            ->defaultValue(2)
                            ->info('1 = transient, 2 = persistent')
                        ->end()
                        ->booleanNode('setup_topology')
                            ->defaultFalse()
                            ->info('Auto-create exchanges')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('queues')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->integerNode('prefetch_count')
                                ->defaultValue(3)
                                ->info('QoS prefetch count')
                            ->end()
                            ->booleanNode('requeue_on_error')
                                ->defaultFalse()
                                ->info('Requeue message on processing error (non-parking lot only)')
                            ->end()
                            ->booleanNode('parking_lot')
                                ->defaultFalse()
                                ->info('Enable parking lot pattern for retries')
                            ->end()
                            ->integerNode('max_retries')
                                ->defaultValue(3)
                                ->info('Max retry attempts (parking lot only)')
                            ->end()
                            ->integerNode('retry_backoff_ms')
                                ->defaultValue(15000)
                                ->info('Retry backoff in milliseconds (parking lot only)')
                            ->end()
                            ->booleanNode('setup_topology')
                                ->defaultFalse()
                                ->info('Auto-create queues and exchanges')
                            ->end()
                            ->booleanNode('setup_bindings')
                                ->defaultFalse()
                                ->info('Auto-create queue bindings')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
