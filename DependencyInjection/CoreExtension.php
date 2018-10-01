<?php

namespace Webkul\UVDesk\CoreBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class CoreExtension extends Extension
{
    public function getAlias()
    {
        return 'uvdesk';
    }

    public function getConfiguration(array $configs, ContainerBuilder $container)
    {
        return new Configuration();
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        
        foreach ($this->processConfiguration($configuration, $configs) as $param => $value) {
            switch ($param) {
                case 'default':
                    foreach ($value as $defaultItem => $defaultItemValue) {
                        switch ($defaultItem) {
                            case 'templates':
                                foreach ($defaultItemValue as $template => $templateValue) {
                                    $container->setParameter("uvdesk.default.templates.$template", $templateValue);
                                }
                                break;
                            default:
                                $container->setParameter("uvdesk.default.$defaultItem", $defaultItemValue);
                                break;
                        }
                    }

                    break;
                default:
                    $container->setParameter("uvdesk.$param", $value);
                    break;
            }
        }
    }
}
