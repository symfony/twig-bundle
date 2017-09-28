<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\TwigBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\TemplateFinderInterface;
use Twig\Error\Error;

/**
 * Generates the Twig cache for all templates.
 *
 * This warmer must be registered after TemplatePathsCacheWarmer,
 * as the Twig loader will need the cache generated by it.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TemplateCacheCacheWarmer implements CacheWarmerInterface
{
    protected $container;
    protected $finder;

    /**
     * @param ContainerInterface           $container The dependency injection container
     * @param TemplateFinderInterface|null $finder    The template paths cache warmer
     */
    public function __construct(ContainerInterface $container, TemplateFinderInterface $finder = null)
    {
        // We don't inject the Twig environment directly as it depends on the
        // template locator (via the loader) which might be a cached one.
        // The cached template locator is available once the TemplatePathsCacheWarmer
        // has been warmed up.
        // But it can also be null if templating has been disabled.
        $this->container = $container;
        $this->finder = $finder;
    }

    /**
     * Warms up the cache.
     *
     * @param string $cacheDir The cache directory
     */
    public function warmUp($cacheDir)
    {
        if (null === $this->finder) {
            return;
        }

        $twig = $this->container->get('twig');

        foreach ($this->finder->findAllTemplates() as $template) {
            if ('twig' !== $template->get('engine')) {
                continue;
            }

            try {
                $twig->loadTemplate($template);
            } catch (Error $e) {
                // problem during compilation, give up
            }
        }
    }

    /**
     * Checks whether this warmer is optional or not.
     *
     * @return bool always true
     */
    public function isOptional()
    {
        return true;
    }
}
