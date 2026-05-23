<?php if (!$has_dashboard_core): ?>
        <div class="alert-falla" role="alert" style="background:#FFF3E0;border-color:#FF9500;">
            <i class="ti ti-database-alert" aria-hidden="true" style="color:#FF9500;"></i>
            <span class="alert-falla-txt" style="color:#5C3E00;">
                El dashboard está conectado, pero no encontró las tablas base necesarias.
                <?php if (isset($_dashboard_debug)): ?>
                <br><small style="display:block;margin-top:5px;opacity:0.8;">
                    Órdenes: <?= htmlspecialchars($_dashboard_debug['orden_encontrada']) ?> |
                    Estados: <?= htmlspecialchars($_dashboard_debug['estado_encontrado']) ?> |
                    Equipos: <?= htmlspecialchars($_dashboard_debug['equipo_encontrado']) ?>
                </small>
                <?php endif; ?>
            </span>
        </div>
        <?php endif; ?>

        <!-- Alerta de fallas urgentes -->
        <?php if ($total_fallas > 0): ?>
        <div class="alert-falla" role="alert">
            <i class="ti ti-alert-triangle" aria-hidden="true"></i>
            <span class="alert-falla-txt">
                <?= $total_fallas ?> equipo<?= $total_fallas > 1 ? 's' : '' ?>
                con falla requiere<?= $total_fallas === 1 ? '' : 'n' ?> atención inmediata
            </span>
            <a href="#ordenes" class="alert-falla-btn">Revisar &rarr;</a>
        </div>
        <?php endif; ?>

        <!-- KPI row 1 -->
        <div class="kpi-grid">
            <?php foreach (array_slice($kpis, 0, 3) as $k): ?>
            <div class="kpi-card"
                 style="background:<?= $k['bg'] ?>;--kpi-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($k['label']) ?>
                </div>
                <div class="kpi-valor" style="color:<?= $k['texto'] ?>;">
                    <?= htmlspecialchars((string)$k['valor']) ?>
                </div>
                <div class="kpi-sub"><?= $k['sub'] /* puede contener HTML */ ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- KPI row 2 -->
        <div class="kpi-grid">
            <?php foreach (array_slice($kpis, 3, 3) as $k): ?>
            <div class="kpi-card"
                 style="background:<?= $k['bg'] ?>;--kpi-color:<?= $k['color'] ?>;">
                <div class="kpi-label" style="color:<?= $k['color'] ?>;">
                    <i class="ti <?= htmlspecialchars($k['icono']) ?>" aria-hidden="true"></i>
                    <?= htmlspecialchars($k['label']) ?>
                </div>
                <div class="kpi-valor" style="color:<?= $k['texto'] ?>;">
                    <?= htmlspecialchars((string)$k['valor']) ?>
                </div>
                <div class="kpi-sub"><?= $k['sub'] ?></div>
            </div>
            <?php endforeach; ?>
        </div>


        <!-- ── Charts ── -->
        <div class="charts-row">

            <!-- Bar chart -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Productividad por técnico</span>
                    <span class="card-pill">Mayo <?= date('Y') ?></span>
                </div>
                <div class="chart-legend">
                    <span class="legend-item">
                        <span class="legend-sq" style="background:#0052CC;"></span>
                        Reparaciones
                    </span>
                    <span class="legend-item">
                        <span class="legend-sq" style="background:#5BA3FF;"></span>
                        Ingresos ($100s)
                    </span>
                </div>
                <div class="chart-wrap" style="height:180px;">
                    <canvas id="barChart"
                            role="img"
                            aria-label="Productividad por técnico desde la base de datos">
                        Productividad por técnico desde la base de datos
                    </canvas>
                </div>
            </div>

            <!-- Donut chart -->
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Distribución de estados</span>
                </div>
                <div class="chart-wrap" style="height:145px;">
                    <canvas id="donutChart"
                            role="img"
                            aria-label="Distribución de estados desde la base de datos">
                        Distribución de estados desde la base de datos
                    </canvas>
                </div>
                <div class="donut-legend">
                    <?php foreach ($chart_estado_labels as $idx => $label): ?>
                    <span class="donut-legend-item">
                        <span class="donut-dot" style="background:<?= h($chart_estado_colors[$idx] ?? '#424242') ?>;"></span>
                        <?= h((string)$label) ?> <?= (int)($chart_estado_values[$idx] ?? 0) ?>%
                    </span>
                    <?php endforeach; ?>
                    <?php if (empty($chart_estado_labels)): ?>
                    <span class="donut-legend-item"><span class="donut-dot" style="background:#D0CCC6;"></span>Sin datos 0%</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- ── Fila inferior ── -->
        <div class="bottom-row">

            <!-- Dispositivos -->
            <div class="dispositivos-card">
                <div class="card-title" style="margin-bottom:14px;">Por tipo de dispositivo</div>
                <?php if (empty($dispositivos)): ?>
                <div style="font-size:11.5px;color:#6B6560;line-height:1.5;">No hay equipos registrados en la base de datos.</div>
                <?php endif; ?>
                <?php foreach ($dispositivos as $d): ?>
                <div class="dispositivo-item">
                    <div class="dispositivo-header">
                        <div class="dispositivo-info">
                            <div class="dispositivo-icon"
                                 style="background:<?= $d['bg'] ?>;color:<?= $d['tc'] ?>;">
                                <i class="ti <?= htmlspecialchars($d['icono']) ?>" aria-hidden="true"></i>
                            </div>
                            <span class="dispositivo-nombre"><?= htmlspecialchars($d['tipo']) ?></span>
                        </div>
                        <span class="dispositivo-pct"><?= $d['pct'] ?>%</span>
                    </div>
                    <div class="barra-track">
                        <div class="barra-fill"
                             style="width:<?= $d['pct'] ?>%;background:<?= $d['color'] ?>;"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>


            <!-- Tabla de órdenes -->
            <div class="ordenes-card" id="ordenes">
                <div class="ordenes-header">
                    <span class="card-title">Órdenes recientes</span>
                    <a href="?page=ordenes" class="ordenes-ver-btn">Ver todas &rarr;</a>
                </div>
                <div class="table-head" role="row" aria-label="Encabezados de tabla">
                    <div>#</div>
                    <div>Cliente / Equipo</div>
                    <div class="col-tecnico">Técnico</div>
                    <div>Estado</div>
                    <div style="text-align:right;">Valor</div>
                </div>
                <div role="list" aria-label="Órdenes recientes" style="max-height:400px;overflow-y:auto;">
                <?php foreach ($ordenes_recientes as $orden): ?>
                <div class="table-row" role="listitem">
                    <div class="order-id"><?= htmlspecialchars($orden['id']) ?></div>
                    <div>
                        <div class="order-cliente"><?= htmlspecialchars($orden['cliente']) ?></div>
                        <div class="order-equipo">
                            <i class="ti <?= htmlspecialchars($orden['icono_eq']) ?>" aria-hidden="true"></i>
                            <?= htmlspecialchars($orden['equipo']) ?>
                        </div>
                    </div>
                    <div class="order-tecnico col-tecnico"><?= htmlspecialchars($orden['tecnico']) ?></div>
                    <div>
                        <span class="status-badge"
                              style="background:<?= $orden['est_bg'] ?>;color:<?= $orden['est_color'] ?>;border-color:<?= $orden['est_borde'] ?>;">
                            <span class="status-dot" style="background:<?= $orden['est_dot'] ?>;"></span>
                            <?= htmlspecialchars($orden['estado']) ?>
                        </span>
                    </div>
                    <div class="order-valor"><?= htmlspecialchars($orden['valor']) ?></div>
                </div>
                <?php endforeach; ?>
                </div>
            </div>

        </div><!-- /bottom-row -->