<?php
/**
 * Page Header Component untuk Staf Penjualan
 * Component untuk header halaman dengan title, deskripsi, dan action button
 */
?>

<div class="page-header px-6 py-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
        <!-- Title dan Description -->
        <div class="flex-1 min-w-0 mb-4 md:mb-0">
            <h1 class="page-title">
                <?= htmlspecialchars($page_title ?? 'Halaman') ?>
            </h1>
            <?php if (isset($page_description)): ?>
                <p class="page-description">
                    <?= htmlspecialchars($page_description) ?>
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Action Buttons -->
        <?php if (isset($header_actions) && is_array($header_actions)): ?>
            <div class="flex flex-col sm:flex-row gap-3">
                <?php foreach ($header_actions as $action): ?>
                    <?php
                    $button_class = 'btn-primary'; // default
                    if (isset($action['type'])) {
                        switch ($action['type']) {
                            case 'secondary':
                                $button_class = 'btn-secondary';
                                break;
                            case 'danger':
                                $button_class = 'btn-danger';
                                break;
                            default:
                                $button_class = 'btn-primary';
                        }
                    }
                    ?>
                    
                    <?php if (isset($action['url'])): ?>
                        <!-- Link Button -->
                        <a href="<?= htmlspecialchars($action['url']) ?>" 
                           class="<?= $button_class ?> <?= $action['class'] ?? '' ?>">
                            <?php if (isset($action['icon'])): ?>
                                <?= $action['icon'] ?>
                            <?php endif; ?>
                            <?= htmlspecialchars($action['label']) ?>
                        </a>
                    <?php else: ?>
                        <!-- Button with onclick -->
                        <button type="button" 
                                class="<?= $button_class ?> <?= $action['class'] ?? '' ?>"
                                <?php if (isset($action['onclick'])): ?>
                                onclick="<?= htmlspecialchars($action['onclick']) ?>"
                                <?php endif; ?>
                                <?php if (isset($action['data']) && is_array($action['data'])): ?>
                                    <?php foreach ($action['data'] as $key => $value): ?>
                                        data-<?= htmlspecialchars($key) ?>="<?= htmlspecialchars($value) ?>"
                                    <?php endforeach; ?>
                                <?php endif; ?>>
                            <?php if (isset($action['icon'])): ?>
                                <?= $action['icon'] ?>
                            <?php endif; ?>
                            <?= htmlspecialchars($action['label']) ?>
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Breadcrumb (optional) -->
    <?php if (isset($breadcrumb) && is_array($breadcrumb)): ?>
        <nav class="mt-4" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2 text-sm">
                <?php foreach ($breadcrumb as $index => $item): ?>
                    <li class="flex items-center">
                        <?php if ($index > 0): ?>
                            <svg class="w-4 h-4 text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        <?php endif; ?>
                        
                        <?php if (isset($item['url']) && $index < count($breadcrumb) - 1): ?>
                            <a href="<?= htmlspecialchars($item['url']) ?>" 
                               class="text-green-600 hover:text-green-800 transition-colors">
                                <?= htmlspecialchars($item['label']) ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-500">
                                <?= htmlspecialchars($item['label']) ?>
                            </span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>
    
    <!-- Stats Cards (optional) -->
    <?php if (isset($stats_cards) && is_array($stats_cards)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mt-6">
            <?php foreach ($stats_cards as $card): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">
                                <?= htmlspecialchars($card['label']) ?>
                            </p>
                            <p class="text-2xl font-semibold text-gray-900">
                                <?= htmlspecialchars($card['value']) ?>
                            </p>
                            <?php if (isset($card['change'])): ?>
                                <p class="text-sm <?= $card['change_type'] === 'increase' ? 'text-green-600' : 'text-red-600' ?>">
                                    <?php if ($card['change_type'] === 'increase'): ?>
                                        <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 17l9.2-9.2M17 17V7h-10"/>
                                        </svg>
                                    <?php else: ?>
                                        <svg class="inline w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7l9.2 9.2M17 7v10H7"/>
                                        </svg>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($card['change']) ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <?php if (isset($card['icon'])): ?>
                            <div class="p-3 bg-green-50 rounded-full">
                                <?= $card['icon'] ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
