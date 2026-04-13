<?php
// app/templates/partials/admin_main_widgets.php

$widgetMonth = new DateTimeImmutable('first day of this month');
$widgetToday = new DateTimeImmutable('today');

$widgetMonthLabel = [
  1 => 'Janeiro',
  2 => 'Fevereiro',
  3 => 'Março',
  4 => 'Abril',
  5 => 'Maio',
  6 => 'Junho',
  7 => 'Julho',
  8 => 'Agosto',
  9 => 'Setembro',
  10 => 'Outubro',
  11 => 'Novembro',
  12 => 'Dezembro',
];

$widgetDayNames = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab', 'Dom'];
$widgetDays = [];

$widgetFirstWeekday = (int)$widgetMonth->format('N');
$widgetDaysInMonth = (int)$widgetMonth->format('t');
$widgetPrevMonth = $widgetMonth->modify('-1 month');
$widgetPrevMonthDays = (int)$widgetPrevMonth->format('t');

for ($i = $widgetFirstWeekday - 1; $i > 0; $i--) {
  $widgetDays[] = [
    'label' => $widgetPrevMonthDays - $i + 1,
    'muted' => true,
    'today' => false,
  ];
}

for ($day = 1; $day <= $widgetDaysInMonth; $day++) {
  $currentDate = $widgetMonth->setDate(
    (int)$widgetMonth->format('Y'),
    (int)$widgetMonth->format('m'),
    $day
  );

  $widgetDays[] = [
    'label' => $day,
    'muted' => false,
    'today' => $currentDate->format('Y-m-d') === $widgetToday->format('Y-m-d'),
  ];
}

while (count($widgetDays) % 7 !== 0) {
  $widgetDays[] = [
    'label' => count($widgetDays) % 7 + 1,
    'muted' => true,
    'today' => false,
  ];
}

$widgetActivities = $widgetActivities ?? [
  ['title' => 'Atualização de cadastro pendente', 'meta' => 'Hoje • Placeholder visual'],
  ['title' => 'Revisão de documentos aguardando etapa futura', 'meta' => 'Amanhã • Sem integração'],
  ['title' => 'Novo atalho administrativo previsto', 'meta' => 'Próxima iteração'],
];

$widgetShortcuts = $widgetShortcuts ?? [
  ['label' => 'Ferramentas', 'icon' => 'fa-solid fa-screwdriver-wrench', 'href' => app_url('/app/templates/ferramentas.php')],
  ['label' => 'Cadastros', 'icon' => 'fa-solid fa-id-card', 'href' => app_url('/app/templates/cadastros.php')],
  ['label' => 'Financeiro', 'icon' => 'fa-solid fa-coins', 'href' => app_url('/app/templates/financeiro.php')],
  ['label' => 'Configurações', 'icon' => 'fa-solid fa-sliders', 'href' => '#'],
];

$widgetCalendarTitle = $widgetCalendarTitle ?? 'Calendário';
$widgetCalendarNote = $widgetCalendarNote ?? 'Visão mensal';
$widgetActivitiesTitle = $widgetActivitiesTitle ?? 'Atividades recentes';
$widgetShortcutsTitle = $widgetShortcutsTitle ?? 'Atalhos administrativos';
$widgetCollapsible = (bool)($widgetCollapsible ?? false);
?>

<section class="admin-block<?= $widgetCollapsible ? ' lot-mobile-collapsible' : '' ?>">
  <div class="admin-block-head">
    <h3 class="admin-block-title"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i><span><?= h($widgetCalendarTitle) ?></span></h3>
    <?php if ($widgetCollapsible): ?>
      <button class="fin-icon-btn fin-icon-btn--sm lot-mobile-toggle" type="button" data-lot-mobile-toggle aria-expanded="true" title="Alternar seção">
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>
    <?php endif; ?>
  </div>
  <div class="admin-block-body">
    <div class="admin-widget-calendar">
      <div class="admin-widget-calendar-bar">
        <div class="admin-widget-calendar-month">
          <?= h($widgetMonthLabel[(int)$widgetMonth->format('n')] . ' ' . $widgetMonth->format('Y')) ?>
        </div>
        <div class="admin-widget-calendar-note"><?= h($widgetCalendarNote) ?></div>
      </div>

      <div class="admin-widget-calendar-grid" aria-label="Calendário administrativo mensal">
        <?php foreach ($widgetDayNames as $dayName): ?>
          <div class="admin-widget-calendar-day-name"><?= h($dayName) ?></div>
        <?php endforeach; ?>

        <?php foreach ($widgetDays as $day): ?>
          <?php
            $dayClasses = ['admin-widget-calendar-day'];
            if (!empty($day['muted'])) $dayClasses[] = 'admin-widget-calendar-day--muted';
            if (!empty($day['today'])) $dayClasses[] = 'admin-widget-calendar-day--today';
          ?>
          <div class="<?= h(implode(' ', $dayClasses)) ?>"><?= h((string)$day['label']) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="admin-block<?= $widgetCollapsible ? ' lot-mobile-collapsible' : '' ?>">
  <div class="admin-block-head">
    <h3 class="admin-block-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><?= h($widgetActivitiesTitle) ?></span></h3>
    <?php if ($widgetCollapsible): ?>
      <button class="fin-icon-btn fin-icon-btn--sm lot-mobile-toggle" type="button" data-lot-mobile-toggle aria-expanded="true" title="Alternar seção">
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>
    <?php endif; ?>
  </div>
  <div class="admin-block-body">
    <?php if ($widgetActivities !== []): ?>
      <div class="admin-widget-activity-list">
        <?php foreach ($widgetActivities as $activity): ?>
          <div class="admin-widget-activity-item">
            <span class="admin-widget-activity-dot" aria-hidden="true"></span>
            <div class="admin-widget-activity-content">
              <p class="admin-widget-activity-title"><?= h((string)($activity['title'] ?? 'Atividade')) ?></p>
              <div class="admin-widget-activity-meta"><?= h((string)($activity['meta'] ?? '')) ?></div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="admin-widget-activity-list">
        <div class="admin-widget-activity-item">
          <span class="admin-widget-activity-dot" aria-hidden="true"></span>
          <div class="admin-widget-activity-content">
            <p class="admin-widget-activity-title">Nenhuma atividade recente</p>
            <div class="admin-widget-activity-meta">Sem movimentações registradas no recorte atual.</div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>
</section>

<section class="admin-block<?= $widgetCollapsible ? ' lot-mobile-collapsible' : '' ?>">
  <div class="admin-block-head">
    <h3 class="admin-block-title"><i class="fa-solid fa-bolt" aria-hidden="true"></i><span><?= h($widgetShortcutsTitle) ?></span></h3>
    <?php if ($widgetCollapsible): ?>
      <button class="fin-icon-btn fin-icon-btn--sm lot-mobile-toggle" type="button" data-lot-mobile-toggle aria-expanded="true" title="Alternar seção">
        <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
      </button>
    <?php endif; ?>
  </div>
  <div class="admin-block-body">
    <div class="admin-widget-shortcuts">
      <?php foreach ($widgetShortcuts as $shortcut): ?>
        <a class="admin-btn admin-widget-shortcut" href="<?= h($shortcut['href']) ?>">
          <span class="admin-btn-icon"><i class="<?= h($shortcut['icon']) ?>"></i></span>
          <span class="admin-btn-label"><?= h($shortcut['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
