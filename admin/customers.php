<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/security-headers.php';
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$pageTitle = 'Clients | Administration Below Dreams';

/*
|--------------------------------------------------------------------------
| Recherche et filtres
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'all');

$allowedFilters = [
    'all',
    'buyers',
    'no_order',
    'top_customers',
    'recent'
];

if (!in_array($filter, $allowedFilters, true)) {
    $filter = 'all';
}

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$currentPage = filter_input(
    INPUT_GET,
    'page',
    FILTER_VALIDATE_INT
);

$currentPage = $currentPage && $currentPage > 0
    ? $currentPage
    : 1;

$perPage = 20;
$offset = ($currentPage - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Conditions SQL
|--------------------------------------------------------------------------
*/

$whereConditions = [];
$havingConditions = [];
$queryParameters = [];

if ($search !== '') {
    $whereConditions[] = "
        (
            customers.firstname LIKE :search
            OR customers.lastname LIKE :search
            OR customers.email LIKE :search
            OR customers.phone LIKE :search
        )
    ";

    $queryParameters['search'] = '%' . $search . '%';
}

switch ($filter) {
    case 'buyers':
        $havingConditions[] = 'order_count > 0';
        break;

    case 'no_order':
        $havingConditions[] = 'order_count = 0';
        break;

    case 'top_customers':
        $havingConditions[] = 'total_spent > 0';
        break;

    case 'recent':
        $whereConditions[] = "
            customers.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ";
        break;
}

$whereSql = $whereConditions
    ? 'WHERE ' . implode(' AND ', $whereConditions)
    : '';

$havingSql = $havingConditions
    ? 'HAVING ' . implode(' AND ', $havingConditions)
    : '';

$orderBySql = $filter === 'top_customers'
    ? 'total_spent DESC, customers.id DESC'
    : 'customers.created_at DESC, customers.id DESC';

/*
|--------------------------------------------------------------------------
| Comptage des résultats
|--------------------------------------------------------------------------
|
| Une sous-requête est utilisée car les filtres acheteurs et meilleurs clients
| reposent sur des valeurs agrégées.
|
*/

$countSql = "
    SELECT COUNT(*)
    FROM (
        SELECT
            customers.id,
            COUNT(DISTINCT orders.id) AS order_count,
            COALESCE(
                SUM(
                    CASE
                        WHEN orders.payment_status = 'paid'
                        THEN orders.total
                        ELSE 0
                    END
                ),
                0
            ) AS total_spent
        FROM customers
        LEFT JOIN orders
            ON orders.customer_id = customers.id
        {$whereSql}
        GROUP BY customers.id
        {$havingSql}
    ) AS filtered_customers
";

$countQuery = $pdo->prepare($countSql);

foreach ($queryParameters as $parameter => $value) {
    $countQuery->bindValue(
        ':' . $parameter,
        $value,
        PDO::PARAM_STR
    );
}

$countQuery->execute();

$totalCustomersFound = (int) $countQuery->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalCustomersFound / $perPage)
);

if ($currentPage > $totalPages) {
    $currentPage = $totalPages;
    $offset = ($currentPage - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| Liste des clients
|--------------------------------------------------------------------------
*/

$customersSql = "
    SELECT
        customers.id,
        customers.firstname,
        customers.lastname,
        customers.email,
        customers.phone,
        customers.created_at,

        COUNT(DISTINCT orders.id) AS order_count,

        COALESCE(
            SUM(
                CASE
                    WHEN orders.payment_status = 'paid'
                    THEN orders.total
                    ELSE 0
                END
            ),
            0
        ) AS total_spent,

        MAX(orders.created_at) AS last_order_at

    FROM customers

    LEFT JOIN orders
        ON orders.customer_id = customers.id

    {$whereSql}

    GROUP BY
        customers.id,
        customers.firstname,
        customers.lastname,
        customers.email,
        customers.phone,
        customers.created_at

    {$havingSql}

    ORDER BY {$orderBySql}

    LIMIT :limit
    OFFSET :offset
";

$customersQuery = $pdo->prepare($customersSql);

foreach ($queryParameters as $parameter => $value) {
    $customersQuery->bindValue(
        ':' . $parameter,
        $value,
        PDO::PARAM_STR
    );
}

$customersQuery->bindValue(
    ':limit',
    $perPage,
    PDO::PARAM_INT
);

$customersQuery->bindValue(
    ':offset',
    $offset,
    PDO::PARAM_INT
);

$customersQuery->execute();

$customers = $customersQuery->fetchAll(PDO::FETCH_ASSOC);

/*
|--------------------------------------------------------------------------
| Statistiques globales
|--------------------------------------------------------------------------
*/

$statisticsQuery = $pdo->query("
    SELECT
        COUNT(DISTINCT customers.id) AS total_customers,

        COUNT(
            DISTINCT CASE
                WHEN orders.payment_status = 'paid'
                THEN customers.id
            END
        ) AS purchasing_customers,

        COUNT(
            DISTINCT CASE
                WHEN customers.created_at >= DATE_SUB(
                    NOW(),
                    INTERVAL 30 DAY
                )
                THEN customers.id
            END
        ) AS recent_customers,

        COALESCE(
            SUM(
                CASE
                    WHEN orders.payment_status = 'paid'
                    THEN orders.total
                    ELSE 0
                END
            ),
            0
        ) AS customer_revenue

    FROM customers

    LEFT JOIN orders
        ON orders.customer_id = customers.id
");

$statistics = $statisticsQuery->fetch(PDO::FETCH_ASSOC);

require_once __DIR__ . '/partials/header.php';
?>

<?php require_once __DIR__ . '/partials/sidebar.php'; ?>

<main class="admin-main">

    <header class="admin-page-header">
        <div>
            <h1>Clients</h1>

            <p>
                Consultez les comptes clients et leur activité
                sur la boutique.
            </p>
        </div>
    </header>

    <section class="admin-stats-grid">

        <article class="admin-stat-card">
            <span>Clients inscrits</span>

            <strong>
                <?= number_format(
                    (int) ($statistics['total_customers'] ?? 0),
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>
        </article>

        <article class="admin-stat-card">
            <span>Clients ayant commandé</span>

            <strong>
                <?= number_format(
                    (int) ($statistics['purchasing_customers'] ?? 0),
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>
        </article>

        <article class="admin-stat-card">
            <span>Nouveaux sur 30 jours</span>

            <strong>
                <?= number_format(
                    (int) ($statistics['recent_customers'] ?? 0),
                    0,
                    ',',
                    ' '
                ) ?>
            </strong>
        </article>

        <article class="admin-stat-card">
            <span>CA clients</span>

            <strong>
                <?= number_format(
                    (float) ($statistics['customer_revenue'] ?? 0),
                    2,
                    ',',
                    ' '
                ) ?> €
            </strong>
        </article>

    </section>

    <section class="admin-card">

        <form
            method="GET"
            action="customers.php"
            class="admin-customers-toolbar"
        >

            <div class="admin-search-field">
                <label for="customer-search">
                    Rechercher
                </label>

                <input
                    type="search"
                    id="customer-search"
                    name="search"
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    maxlength="190"
                    placeholder="Nom, prénom, email ou téléphone"
                >
            </div>

            <div class="admin-filter-field">
                <label for="customer-filter">
                    Filtrer
                </label>

                <select
                    id="customer-filter"
                    name="filter"
                >
                    <option
                        value="all"
                        <?= $filter === 'all' ? 'selected' : '' ?>
                    >
                        Tous les clients
                    </option>

                    <option
                        value="buyers"
                        <?= $filter === 'buyers' ? 'selected' : '' ?>
                    >
                        Ayant commandé
                    </option>

                    <option
                        value="no_order"
                        <?= $filter === 'no_order' ? 'selected' : '' ?>
                    >
                        Sans commande
                    </option>

                    <option
                        value="top_customers"
                        <?= $filter === 'top_customers'
                            ? 'selected'
                            : '' ?>
                    >
                        Meilleurs clients
                    </option>

                    <option
                        value="recent"
                        <?= $filter === 'recent' ? 'selected' : '' ?>
                    >
                        Inscrits depuis 30 jours
                    </option>
                </select>
            </div>

            <div class="admin-customers-toolbar-actions">

                <button type="submit" class="admin-button">
                    Appliquer
                </button>

                <?php if (
                    $search !== ''
                    || $filter !== 'all'
                ) : ?>
                    <a
                        href="customers.php"
                        class="admin-button admin-button-secondary"
                    >
                        Réinitialiser
                    </a>
                <?php endif; ?>

            </div>

        </form>

        <div class="admin-results-header">
            <h2>Liste des clients</h2>

            <span>
                <?= number_format(
                    $totalCustomersFound,
                    0,
                    ',',
                    ' '
                ) ?>
                résultat<?= $totalCustomersFound > 1 ? 's' : '' ?>
            </span>
        </div>

        <?php if (empty($customers)) : ?>

            <div class="admin-empty-state">
                <h3>Aucun client trouvé</h3>

                <p>
                    Aucun compte ne correspond aux critères sélectionnés.
                </p>
            </div>

        <?php else : ?>

            <div class="admin-table-wrapper">

                <table class="admin-table admin-customers-table">

                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Coordonnées</th>
                            <th>Inscription</th>
                            <th>Commandes</th>
                            <th>Total dépensé</th>
                            <th>Dernière commande</th>
                            <th>Statut</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($customers as $customer) : ?>

                            <?php
                            $customerName = trim(
                                ($customer['firstname'] ?? '')
                                . ' '
                                . ($customer['lastname'] ?? '')
                            );

                            $orderCount =
                                (int) $customer['order_count'];

                            $totalSpent =
                                (float) $customer['total_spent'];
                            ?>

                            <tr>

                                <td data-label="Client">
                                    <div class="admin-customer-identity">

                                        <span class="admin-customer-avatar">
                                            <?= htmlspecialchars(
                                                mb_strtoupper(
                                                    mb_substr(
                                                        $customer['firstname']
                                                            ?? '?',
                                                        0,
                                                        1
                                                    )
                                                    . mb_substr(
                                                        $customer['lastname']
                                                            ?? '',
                                                        0,
                                                        1
                                                    )
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </span>

                                        <div>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $customerName !== ''
                                                        ? $customerName
                                                        : 'Client sans nom',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>

                                            <span>
                                                Client #<?= (int) $customer['id'] ?>
                                            </span>
                                        </div>

                                    </div>
                                </td>

                                <td data-label="Coordonnées">
                                    <div class="admin-customer-contact">

                                        <a
                                            href="mailto:<?= htmlspecialchars(
                                                $customer['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>"
                                        >
                                            <?= htmlspecialchars(
                                                $customer['email'],
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </a>

                                        <?php if (!empty($customer['phone'])) : ?>
                                            <a
                                                href="tel:<?= htmlspecialchars(
                                                    $customer['phone'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>"
                                            >
                                                <?= htmlspecialchars(
                                                    $customer['phone'],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </a>
                                        <?php else : ?>
                                            <span>Non renseigné</span>
                                        <?php endif; ?>

                                    </div>
                                </td>

                                <td data-label="Inscription">
                                    <?= !empty($customer['created_at'])
                                        ? date(
                                            'd/m/Y',
                                            strtotime(
                                                $customer['created_at']
                                            )
                                        )
                                        : 'Non renseignée' ?>
                                </td>

                                <td data-label="Commandes">
                                    <strong>
                                        <?= $orderCount ?>
                                    </strong>
                                </td>

                                <td data-label="Total dépensé">
                                    <strong>
                                        <?= number_format(
                                            $totalSpent,
                                            2,
                                            ',',
                                            ' '
                                        ) ?> €
                                    </strong>
                                </td>

                                <td data-label="Dernière commande">
                                    <?= !empty($customer['last_order_at'])
                                        ? date(
                                            'd/m/Y à H:i',
                                            strtotime(
                                                $customer['last_order_at']
                                            )
                                        )
                                        : 'Aucune' ?>
                                </td>

                                <td data-label="Statut">
                                    <?php if ($orderCount > 0) : ?>
                                        <span class="admin-status admin-status-success">
                                            Client
                                        </span>
                                    <?php else : ?>
                                        <span class="admin-status admin-status-neutral">
                                            Inscrit
                                        </span>
                                    <?php endif; ?>
                                </td>

                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <?php if ($totalPages > 1) : ?>

                <nav
                    class="admin-pagination"
                    aria-label="Pagination des clients"
                >

                    <?php if ($currentPage > 1) : ?>
                        <a
                            href="?<?= http_build_query([
                                'search' => $search,
                                'filter' => $filter,
                                'page' => $currentPage - 1
                            ]) ?>"
                        >
                            ← Précédent
                        </a>
                    <?php endif; ?>

                    <span>
                        Page <?= $currentPage ?> sur <?= $totalPages ?>
                    </span>

                    <?php if ($currentPage < $totalPages) : ?>
                        <a
                            href="?<?= http_build_query([
                                'search' => $search,
                                'filter' => $filter,
                                'page' => $currentPage + 1
                            ]) ?>"
                        >
                            Suivant →
                        </a>
                    <?php endif; ?>

                </nav>

            <?php endif; ?>

        <?php endif; ?>

    </section>

</main>

<?php require_once __DIR__ . '/partials/footer.php'; ?>