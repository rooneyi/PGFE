@props([
    'extras' => [],
    'current' => '',
    'backUrl' => null,
    'backLabel' => null,
])

@php
    $menuService = app(\App\Services\MenuService\AdminMenuService::class);
    $bc = $menuService->buildBreadcrumb($extras, $current ?: null);
@endphp

<x-breadcrumb
    :links="$bc['links']"
    :current="$bc['current']"
    :back-url="$backUrl ?? $bc['backUrl']"
    :back-label="$backLabel ?? $bc['backLabel']"
/>
