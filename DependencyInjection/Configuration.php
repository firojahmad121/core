<?php

namespace Webkul\UVDesk\TicketBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('uvdesk')
            ->children()
                ->node('domain', 'scalar')->cannotBeEmpty()->end()
                ->node('mailbox', 'array')
                    ->arrayPrototype()
                        ->children()
                            ->node('host', 'scalar')->cannotBeEmpty()->end()
                            ->node('email', 'scalar')->cannotBeEmpty()->end()
                            ->node('password', 'scalar')->end()
                            ->node('enabled', 'boolean')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
                ->node('default', 'array')
                    ->children()
                        ->node('mailbox', 'scalar')->end()
                        ->node('status', 'scalar')->cannotBeEmpty()->end()
                        ->node('priority', 'scalar')->cannotBeEmpty()->end()
                        ->node('type', 'scalar')->cannotBeEmpty()->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
