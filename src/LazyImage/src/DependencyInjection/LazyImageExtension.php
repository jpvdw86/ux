<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LazyImage\DependencyInjection;

use Intervention\Image\ImageManager;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\UX\LazyImage\BlurHash\BlurHash;
use Symfony\UX\LazyImage\BlurHash\BlurHashInterface;
use Symfony\UX\LazyImage\BlurHash\CachedBlurHash;
use Symfony\UX\LazyImage\Twig\BlurHashExtension;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
class LazyImageExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        if (class_exists(ImageManager::class)) {
            $container
                ->setDefinition('lazy_image.image_manager', new Definition(ImageManager::class))
                ->setPublic(false)
            ;
        }

        $container
            ->setDefinition('lazy_image.blur_hash', new Definition(BlurHash::class))
            ->addArgument(new Reference('lazy_image.image_manager', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->setPublic(false)
        ;

        $container->setAlias(BlurHashInterface::class, 'lazy_image.blur_hash')->setPublic(false);

        if (isset($config['cache'])) {
            $container
                ->setDefinition('lazy_image.cached_blur_hash', new Definition(CachedBlurHash::class))
                ->setDecoratedService('lazy_image.blur_hash')
                ->addArgument(new Reference('lazy_image.cached_blur_hash.inner'))
                ->addArgument(new Reference($config['cache']))
            ;

            $container->setAlias(BlurHashInterface::class, 'lazy_image.blur_hash')->setPublic(false);
        }

        $container
            ->setDefinition('twig.extension.blur_hash', new Definition(BlurHashExtension::class))
            ->addArgument(new Reference('lazy_image.blur_hash'))
            ->addTag('twig.extension')
            ->setPublic(false)
        ;
    }

    public function prepend(ContainerBuilder $container)
    {
        if (!$this->isAssetMapperAvailable($container)) {
            return;
        }

        $container->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [
                    __DIR__.'/../../assets/dist' => '@symfony/ux-lazy-image',
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
