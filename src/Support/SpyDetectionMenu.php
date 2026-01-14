<?php

declare(strict_types=1);

namespace Seat\SpyDetection\Support;

class SpyDetectionMenu
{
    public static function register(): void
    {
        if (!class_exists(\Seat\Services\Configuration\Menu::class)) {
            return;
        }

        try {
            $menu = \Seat\Services\Configuration\Menu::add(
                'Spy Detection',
                'spy-detection.view',
                'spy-detection.index'
            );

            if (method_exists($menu, 'setIcon')) {
                $menu->setIcon('fa-user-secret');
            }
            if (method_exists($menu, 'setGroup')) {
                $menu->setGroup('Recruiting');
            }
            if (method_exists($menu, 'setDescription')) {
                $menu->setDescription('Manual spy detection scan');
            }
        } catch (\Throwable) {
            // Menu registration is optional and should never block boot.
        }
    }
}
