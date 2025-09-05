<?php
// admin-sections/dashboard.php - PULITO
try {
    // Contatori principali
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM giocatore");
    $stmt->execute();
    $totalCalciatori = $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM fantasquadra WHERE flag_attuale = '1'");
    $stmt->execute();
    $totalPartecipanti = $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM competizione_disputata");
    $stmt->execute();
    $totalCompetizioni = $stmt->fetch()['count'];

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM immagine WHERE flag_visibile = '1'");
    $stmt->execute();
    $totalImmagini = $stmt->fetch()['count'];

    // Visitatori oggi
    $stmt = $conn->prepare("SELECT SUM(views) as count FROM page_views WHERE date = CURDATE()");
    $stmt->execute();
    $visitorsToday = $stmt->fetch()['count'] ?: 0;

    // Utenti attivi (ultima ora)
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM sessions WHERE last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute();
    $activeUsers = $stmt->fetch()['count'];

    // Top 10 pagine più visitate (ultimo mese)
    $stmt = $conn->prepare("
        SELECT page_url, SUM(views) as total_views 
        FROM page_views 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY page_url 
        ORDER BY total_views DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $topPages = $stmt->fetchAll();

    // Visitatori ultimi 30 giorni
    $stmt = $conn->prepare("
        SELECT date, SUM(views) as daily_views 
        FROM page_views 
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY date 
        ORDER BY date DESC
    ");
    $stmt->execute();
    $dailyVisitors = $stmt->fetchAll();

    // Competizione più recente
    $stmt = $conn->prepare("
        SELECT cd.*, c.tipologia 
        FROM competizione_disputata cd
        JOIN competizione c ON cd.nome_competizione = c.nome_competizione
        ORDER BY cd.anno DESC, cd.id_competizione_disputata DESC 
        LIMIT 1
    ");
    $stmt->execute();
    $recentCompetition = $stmt->fetch();

} catch (Exception $e) {
    $error = "Errore nel recupero delle statistiche: " . $e->getMessage();
}
?>

<div class="section-header">
    <h1 class="section-title">Dashboard Amministrativa</h1>
    <p class="section-subtitle">Panoramica generale del sistema e statistiche</p>
</div>

<?php if (isset($_GET['check'])): ?>
    <div class="alert alert-info">
        <span class="material-icons">info</span>
        <?= htmlspecialchars($_GET['check']) ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <span class="material-icons">error</span>
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">sports_soccer</span>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalCalciatori ?? 0) ?></div>
            <div class="stat-label">Calciatori Totali</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">groups</span>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalPartecipanti ?? 0) ?></div>
            <div class="stat-label">Partecipanti Attivi</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">emoji_events</span>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalCompetizioni ?? 0) ?></div>
            <div class="stat-label">Competizioni</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">photo_library</span>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($totalImmagini ?? 0) ?></div>
            <div class="stat-label">Immagini Gallery</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">visibility</span>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($visitorsToday ?? 0) ?></div>
            <div class="stat-label">Visitatori Oggi</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon">
            <span class="material-icons">people</span>
        </div>
        <div class="stat-content">
            <div class="stat-value" id="activeUsersCount"><?= number_format($activeUsers ?? 0) ?></div>
            <div class="stat-label">Utenti Attivi</div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="dashboard-charts">
    <div class="row">
        <!-- Visitatori Chart -->
        <div class="col-lg-8">
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">trending_up</span>
                        Visitatori Giornalieri (Ultimi 30 giorni)
                    </h3>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="visitorsChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Utenti Attivi Chart -->
        <div class="col-lg-4">
            <div class="admin-card">
                <div class="card-header">
                    <h3 class="card-title">
                        <span class="material-icons">people</span>
                        Utenti Attivi (Real-time)
                    </h3>
                </div>
                <div class="chart-container" style="height: 300px;">
                    <canvas id="activeUsersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Pages e Recent Activity -->
<div class="row">
    <!-- Top 10 Pagine -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="material-icons">bar_chart</span>
                    Top 10 Pagine Visitate
                </h3>
            </div>
            <div class="top-pages-container">
                <?php if (!empty($topPages)): ?>
                    <?php foreach ($topPages as $index => $page): ?>
                        <div class="top-page-item">
                            <div class="page-rank"><?= $index + 1 ?></div>
                            <div class="page-info">
                                <div class="page-url"><?= htmlspecialchars($page['page_url']) ?></div>
                                <div class="page-views"><?= number_format($page['total_views']) ?> visite</div>
                            </div>
                            <div class="page-bar">
                                <div class="page-bar-fill" style="width: <?= ($page['total_views'] / $topPages[0]['total_views']) * 100 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-data">
                        <span class="material-icons">info</span>
                        <p>Nessun dato disponibile</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="material-icons">history</span>
                    Attività Recente
                </h3>
            </div>
            <div class="recent-activity">
                <?php if (isset($recentCompetition)): ?>
                    <div class="activity-item">
                        <div class="activity-icon">
                            <span class="material-icons">emoji_events</span>
                        </div>
                        <div class="activity-content">
                            <div class="activity-title">Ultima Competizione</div>
                            <div class="activity-desc">
                                <?= htmlspecialchars($recentCompetition['nome_competizione']) ?> 
                                (<?= htmlspecialchars($recentCompetition['anno']) ?>)
                            </div>
                            <?php if ($recentCompetition['vincitore']): ?>
                                <div class="activity-meta">
                                    Vincitore: <?= htmlspecialchars($recentCompetition['vincitore']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="activity-item">
                    <div class="activity-icon">
                        <span class="material-icons">update</span>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Sistema</div>
                        <div class="activity-desc">Dashboard caricata correttamente</div>
                        <div class="activity-meta"><?= date('d/m/Y H:i') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Passa i dati PHP a JavaScript per i charts
window.dashboardData = {
    visitorsData: <?= json_encode(array_reverse(array_column($dailyVisitors ?? [], 'daily_views'))) ?>,
    visitorsLabels: <?= json_encode(array_reverse(array_map(function($item) { 
        return date('d/m', strtotime($item['date'])); 
    }, $dailyVisitors ?? []))) ?>
};
</script>