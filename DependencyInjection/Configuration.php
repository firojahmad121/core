<?php

namespace Webkul\UVDesk\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('uvdesk')
            ->children()
                ->node('site_url', 'scalar')->defaultValue('127.0.0.1')->end()
                ->node('email_domain', 'scalar')->defaultValue('@localhost')->end()
                ->node('default', 'array')
                    ->children()
                        ->node('first_run', 'scalar')->defaultValue('disabled')->end()
                        ->node('templates', 'array')
                            ->children()
                                ->node('email', 'scalar')->defaultValue('mail.html.twig')->end()
                            ->end()
                    ->end()
                ->end()
            ->end();

            // ->children()
            //     ->node('domain', 'scalar')->cannotBeEmpty()->end()
            //     ->node('mailbox', 'array')
            //         ->arrayPrototype()
            //             ->children()
            //                 ->node('host', 'scalar')->cannotBeEmpty()->end()
            //                 ->node('email', 'scalar')->cannotBeEmpty()->end()
            //                 ->node('password', 'scalar')->end()
            //                 ->node('enabled', 'boolean')->defaultFalse()->end()
            //             ->end()
            //         ->end()
            //     ->end()
            //     ->node('default', 'array')
            //         ->children()
            //             ->node('mailbox', 'scalar')->end()
            //             ->node('status', 'scalar')->cannotBeEmpty()->end()
            //             ->node('priority', 'scalar')->cannotBeEmpty()->end()
            //             ->node('type', 'scalar')->cannotBeEmpty()->end()
            //         ->end()
            //     ->end()

        return $treeBuilder;
    }
}
