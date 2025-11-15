<div class="d-flex flex-column flex-shrink-0 p-3 bg-light" style="min-height: calc(100vh - 56px);">
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?= url('/') ?>" class="nav-link <?= (($_SERVER['REQUEST_URI'] ?? '') === '/monbudgetV2/' || ($_SERVER['REQUEST_URI'] ?? '') === '/monbudgetV2') ? 'active' : '' ?>">
                <i class="bi bi-house-door me-2"></i>
                Tableau de bord
            </a>
        </li>
        <li>
            <a href="<?= url('comptes') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/comptes') ? 'active' : '' ?>">
                <i class="bi bi-bank me-2"></i>
                Comptes
            </a>
        </li>
        <li>
            <a href="<?= url('transactions') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/transactions') ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right me-2"></i>
                Transactions
            </a>
        </li>
        <li>
            <a href="<?= url('categories') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/categories') ? 'active' : '' ?>">
                <i class="bi bi-tags me-2"></i>
                Catégories
            </a>
        </li>
        <li>
            <a href="<?= url('tiers') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/tiers') ? 'active' : '' ?>">
                <i class="bi bi-people me-2"></i>
                Tiers
            </a>
        </li>
        <li>
            <a href="<?= url('budgets') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/budgets') ? 'active' : '' ?>">
                <i class="bi bi-pie-chart me-2"></i>
                Budgets
            </a>
        </li>
        <li class="mt-3">
            <hr>
        </li>
        <li>
            <a href="<?= url('rapports') ?>" class="nav-link <?= str_contains(($_SERVER['REQUEST_URI'] ?? ''), '/rapports') ? 'active' : '' ?>">
                <i class="bi bi-bar-chart me-2"></i>
                Rapports
            </a>
        </li>
    </ul>
</div>
