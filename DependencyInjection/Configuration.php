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
                ->node('upload_manager', 'array')
                    ->children()
                        ->node('id', 'scalar')->defaultValue('uvdesk.core.fs.upload.manager')->end()
                    ->end()
                ->end()
                ->node('default', 'array')
                    ->children()
                        ->node('templates', 'array')
                            ->children()
                                ->node('email', 'scalar')->defaultValue('mail.html.twig')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->node('mailboxes', 'array')
                    ->arrayPrototype()
                        ->children()
                            ->node('host', 'scalar')->cannotBeEmpty()->end()
                            ->node('email', 'scalar')->cannotBeEmpty()->end()
                            ->node('password', 'scalar')->end()
                            ->node('enabled', 'boolean')->defaultFalse()->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
