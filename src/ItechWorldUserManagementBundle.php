<?php

declare(strict_types = 1);

namespace ItechWorld\UserManagementBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class ItechWorldUserManagementBundle extends AbstractBundle
{
    public function loadExtension(
        array $config,
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Charge tous les fichiers de configuration
        $container->import('../config/services.yaml');
    }

    public function prependExtension(
        ContainerConfigurator $container,
        ContainerBuilder $builder
    ): void {
        // Config API Platform
        if ($builder->hasExtension('api_platform')) {
            // Config pour le dev uniquement
            if ($builder->getParameter('kernel.environment') === 'dev') {
                $container->extension('api_platform', [
                    'enable_swagger_ui' => true,
                    'swagger' => [
                        'swagger_ui_extra_configuration' => [
                            'docExpansion' => 'none',
                            'filter' => true,
                        ],
                        'api_keys' => [
                            'JWT' => [
                                'name' => 'Authorization',
                                'type' => 'header',
                            ]
                        ]
                    ]
                ]);
            }
        }

        // Config CORS
        if ($builder->hasExtension('nelmio_cors')) {
            $container->extension('nelmio_cors', [
                'defaults' => [
                    'origin_regex' => true,
                    'allow_origin' => ['%env(default::CORS_ALLOW_ORIGIN)%'],
                    'allow_methods' => ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'],
                    'allow_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                    'expose_headers' => ['Link'],
                    'max_age' => 3600
                ],
                'paths' => [
                    '^/' => null
                ]
            ]);
        }
    }
}
