<?php

namespace App\Support;

use Illuminate\Routing\Route as RoutingRoute;

class BackendPermission
{
    public const ACTIONS = ['all', 'details', 'show', 'pending', 'confirmed', 'packaging', 'shipped', 'delivered', 'cancelled', 'create', 'update', 'delete'];

    public const CRUD_ACTIONS = ['show', 'create', 'update', 'delete'];

    public const ORDER_STATUS_ACTIONS = ['pending', 'confirmed', 'packaging', 'processing', 'shipped', 'delivered', 'cancelled'];

    public static function fromRoute(?RoutingRoute $route): ?string
    {
        if (! $route) {
            return null;
        }

        $name = $route->getName();

        if (! $name || ! str_starts_with($route->uri(), 'backend') || $name === 'dashboard') {
            return null;
        }

        return self::fromRouteName($name);
    }

    public static function fromRouteName(string $routeName): string
    {
        [$section, $action] = self::parts($routeName);

        return "{$section}.".self::normalizeAction($section, $action);
    }

    public static function sectionFromRouteName(string $routeName): string
    {
        return self::parts($routeName)[0];
    }

    public static function allForSection(string $section): array
    {
        if (in_array($section, ['profit-loss-report', 'moderator-order-report', 'total-order-report'], true)) {
            return ["{$section}.show"];
        }

        if ($section === 'orders') {
            return collect(['all', 'search', 'details', 'create', 'update', 'delete'])
                ->map(fn ($action) => "{$section}.{$action}")
                ->all();
        }

        return collect(self::CRUD_ACTIONS)
            ->map(fn ($action) => "{$section}.{$action}")
            ->all();
    }

    protected static function parts(string $routeName): array
    {
        $parts = explode('.', $routeName, 2);

        return [$parts[0], $parts[1] ?? 'show'];
    }

    protected static function normalizeAction(string $section, string $action): string
    {
        if ($section === 'orders' && ($action === 'search' || str_starts_with($action, 'search.'))) {
            return 'search';
        }

        if ($section === 'orders' && in_array($action, self::ORDER_STATUS_ACTIONS, true)) {
            return $action === 'processing' ? 'packaging' : $action;
        }

        if ($section === 'orders' && ($action === 'view' || str_contains($action, 'invoice') || str_contains($action, 'receipt'))) {
            return 'details';
        }

        if (str_contains($action, 'create') || $action === 'store' || str_contains($action, 'duplicate')) {
            return 'create';
        }

        if (str_contains($action, 'edit') || str_contains($action, 'update') || str_contains($action, 'status')) {
            return 'update';
        }

        if (str_contains($action, 'delete') || str_contains($action, 'destroy')) {
            return 'delete';
        }

        if ($section === 'orders') {
            return 'all';
        }

        return 'show';
    }
}
