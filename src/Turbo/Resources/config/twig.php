<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Kévin Dunglas <kevin@dunglas.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Symfony\UX\Turbo\Twig\StreamExtension;

/*
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
return static function (ContainerConfigurator $container): void {
    $container
        ->services()
            ->set('turbo.twig.extension.stream', StreamExtension::class)
            ->args([
                service('webpack_encore.twig_stimulus_extension'),
                service('property_accessor'),
            ])
            ->tag('twig.extension')
    ;
};
