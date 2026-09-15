<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>TESCHA | Panel de Administración General</title>

  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    * { box-sizing: border-box; }

    body {
      margin: 0;
      background: #f6f4f2;
      color: #243044;
      font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif;
    }

    .shell {
      display: grid;
      grid-template-columns: 242px minmax(0, 1fr);
      min-height: 100vh;
    }

    .sidebar {
      display: flex;
      flex-direction: column;
      padding: 26px 14px 16px;
      color: #fff;
      background: linear-gradient(180deg, #56132d, #6d1938);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 11px;
      padding: 0 12px 24px;
      border-bottom: 1px solid rgba(255, 255, 255, .14);
    }

    .logo-mark {
      display: grid;
      place-items: center;
      width: 42px;
      height: 42px;
      border-radius: 13px;
      color: #f4d894;
      background: rgba(255, 255, 255, .12);
    }

    .logo-mark svg { width: 23px; }

    .logo b {
      display: block;
      font-size: 17px;
      letter-spacing: -.03em;
    }

    .logo span {
      display: block;
      margin-top: 2px;
      color: rgba(255, 255, 255, .58);
      font-size: 10px;
      font-weight: 700;
      letter-spacing: .09em;
    }

    .nav {
      display: grid;
      gap: 4px;
      margin-top: 14px;
      overflow-y: auto;
    }

    .nav-title {
      margin: 14px 12px 7px;
      color: rgba(255, 255, 255, .46);
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .12em;
    }

    .nav button {
      display: flex;
      align-items: center;
      width: 100%;
      gap: 11px;
      padding: 10px 12px;
      border: 0;
      border-radius: 11px;
      color: rgba(255, 255, 255, .73);
      background: transparent;
      cursor: pointer;
      font: 600 13px inherit;
      text-align: left;
      transition: .18s;
    }

    .nav button svg {
      width: 18px;
      height: 18px;
    }

    .nav button:hover,
    .nav button.active {
      color: #fff;
      background: rgba(255, 255, 255, .12);
    }

    .nav button.active {
      box-shadow: inset 3px 0 #e0b664;
    }

    .account {
      display: flex;
      align-items: center;
      gap: 10px;
      margin-top: auto;
      padding: 16px 10px 3px;
      border-top: 1px solid rgba(255, 255, 255, .14);
    }

    .avatar {
      display: grid;
      place-items: center;
      width: 34px;
      height: 34px;
      border-radius: 50%;
      color: #fff;
      background: #8e2449;
      font-size: 12px;
      font-weight: 800;
    }

    .avatar.admin-avatar {
      background: #c96b00;
    }

    .account b,
    .account span {
      display: block;
      max-width: 145px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .account b { font-size: 12px; }

    .account span {
      margin-top: 2px;
      color: rgba(255, 255, 255, .57);
      font-size: 11px;
    }

    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      height: 74px;
      padding: 0 30px;
      border-bottom: 1px solid #e9e5e2;
      background: #fff;
    }

    .breadcrumb {
      display: flex;
      align-items: center;
      gap: 9px;
      color: #8993a4;
      font-size: 13px;
    }

    .breadcrumb b { color: #691936; }

    .breadcrumb i {
      color: #c1c6cf;
      font-style: normal;
    }

    .user {
      display: flex;
      align-items: center;
      gap: 13px;
    }

    .role-badge {
      padding: 3px 8px;
      border-radius: 6px;
      color: #691936;
      background: #fce8ef;
      font-size: 10px;
      font-weight: 800;
      letter-spacing: .05em;
    }

    .notify {
      display: grid;
      place-items: center;
      position: relative;
      width: 36px;
      height: 36px;
      border: 0;
      color: #677287;
      background: transparent;
      cursor: pointer;
    }

    .notify::after {
      content: "";
      position: absolute;
      top: 8px;
      right: 7px;
      width: 7px;
      height: 7px;
      border: 2px solid #fff;
      border-radius: 50%;
      background: #92264b;
    }

    .notify svg { width: 20px; }

    .user .avatar {
      width: 38px;
      height: 38px;
    }

    .user-info b {
      display: block;
      font-size: 13px;
    }

    .user-info span {
      display: block;
      margin-top: 2px;
      color: #8a94a6;
      font-size: 11px;
    }

    .content { padding: 32px; }

    .content h1 {
      margin: 0;
      color: #202b3d;
      font-size: 29px;
      letter-spacing: -.045em;
    }

    .welcome {
      margin: 6px 0 27px;
      color: #748095;
      font-size: 14px;
    }

    .period {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 18px;
      padding: 20px 24px;
      border-radius: 17px;
      color: #fff;
      background: linear-gradient(100deg, #5b1330, #791e41);
      box-shadow: 0 12px 25px rgba(92, 19, 48, .16);
    }

    .period-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .period-icon {
      display: grid;
      place-items: center;
      width: 48px;
      height: 48px;
      border-radius: 13px;
      color: #f5d891;
      background: rgba(255, 255, 255, .13);
    }

    .period-icon svg { width: 23px; }

    .period-label {
      color: rgba(255, 255, 255, .64);
      font-size: 11px;
      font-weight: 800;
      letter-spacing: .08em;
    }

    .period strong {
      display: block;
      margin-top: 4px;
      font-size: 20px;
      letter-spacing: -.03em;
    }

    .period-meta {
      display: flex;
      align-items: center;
      gap: 14px;
    }

    .weeks {
      min-width: 130px;
      padding: 9px 14px;
      border-radius: 11px;
      background: rgba(255, 255, 255, .13);
      text-align: center;
    }

    .weeks b { font-size: 16px; }

    .weeks span {
      display: block;
      margin-top: 1px;
      color: rgba(255, 255, 255, .65);
      font-size: 11px;
    }

    .status {
      display: flex;
      align-items: center;
      gap: 7px;
      padding: 10px 13px;
      border-radius: 999px;
      color: #73f0ae;
      background: rgba(33, 196, 121, .18);
      font-size: 12px;
      font-weight: 800;
    }

    .status i {
      width: 7px;
      height: 7px;
      border-radius: 50%;
      background: #36dd88;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(4, minmax(0, 1fr));
      gap: 16px;
      margin-top: 22px;
    }

    .stat {
      padding: 19px;
      border: 1px solid #ece9e6;
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 5px 13px rgba(31, 38, 50, .045);
    }

    .stat-icon {
      display: grid;
      place-items: center;
      width: 38px;
      height: 38px;
      border-radius: 11px;
    }

    .stat-icon svg { width: 20px; }

    .burgundy { color: #8c2448; background: #f8edf1; }
    .blue { color: #2563eb; background: #edf4ff; }
    .gold { color: #c96b00; background: #fff8e7; }
    .green { color: #009e4f; background: #eafaf1; }

    .stat b {
      display: block;
      margin-top: 19px;
      color: #172033;
      font-size: 29px;
      letter-spacing: -.06em;
    }

    .stat strong {
      display: block;
      margin-top: 4px;
      color: #657187;
      font-size: 14px;
    }

    .stat span {
      display: block;
      margin-top: 7px;
      color: #9ba5b5;
      font-size: 11px;
    }

    .section {
      margin-top: 22px;
      padding: 23px;
      border: 1px solid #ece9e6;
      border-radius: 17px;
      background: #fff;
      box-shadow: 0 5px 13px rgba(31, 38, 50, .04);
    }

    .section h2,
    .panel-head h2 {
      margin: 0;
      color: #293448;
      font-size: 17px;
    }

    .quick {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 12px;
      margin-top: 17px;
    }

    .action {
      min-height: 124px;
      padding: 16px;
      border: 1px solid #f0eeec;
      border-radius: 13px;
      color: #253146;
      background: #fff;
      cursor: pointer;
      font: inherit;
      text-align: center;
    }

    .action:hover {
      border-color: #ddc3cd;
      background: #fffafa;
    }

    .action-icon {
      display: grid;
      place-items: center;
      width: 42px;
      height: 42px;
      margin: 0 auto 12px;
      border-radius: 12px;
    }

    .action-icon svg { width: 21px; }

    .action b {
      display: block;
      font-size: 13px;
    }

    .action span {
      display: block;
      margin-top: 4px;
      color: #929cad;
      font-size: 11px;
    }

    .bottom {
      display: grid;
      grid-template-columns: 1.45fr .95fr;
      gap: 20px;
      margin-top: 22px;
    }

    .feed,
    .careers {
      overflow: hidden;
      border: 1px solid #ece9e6;
      border-radius: 17px;
      background: #fff;
      box-shadow: 0 5px 13px rgba(31, 38, 50, .04);
    }

    .panel-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 21px 23px;
      border-bottom: 1px solid #f1efed;
    }

    .panel-head a {
      color: #8b2346;
      font-size: 12px;
      font-weight: 800;
      text-decoration: none;
    }

    .event {
      display: grid;
      grid-template-columns: 42px 1fr auto;
      gap: 12px;
      align-items: center;
      padding: 16px 23px;
      border-bottom: 1px solid #f6f4f2;
    }

    .event:last-child { border-bottom: 0; }

    .event-icon {
      display: grid;
      place-items: center;
      width: 38px;
      height: 38px;
      border-radius: 10px;
    }

    .event-icon svg { width: 18px; }

    .event b {
      display: block;
      color: #283449;
      font-size: 13px;
    }

    .event span {
      display: block;
      margin-top: 3px;
      color: #929cad;
      font-size: 11px;
    }

    .time {
      color: #98a2b2;
      font-size: 11px;
      text-align: right;
      white-space: nowrap;
    }

    .career {
      padding: 15px 23px;
      border-bottom: 1px solid #f6f4f2;
    }

    .career-top {
      display: flex;
      align-items: center;
      gap: 9px;
    }

    .code {
      padding: 4px 6px;
      border-radius: 5px;
      color: #fff;
      background: #8b2346;
      font-size: 10px;
      font-weight: 800;
    }

    .career-name {
      flex: 1;
      min-width: 0;
      overflow: hidden;
      color: #354156;
      font-size: 12px;
      font-weight: 700;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .arrow {
      color: #8b2346;
      font-size: 16px;
      font-weight: 800;
    }

    .career-foot {
      display: flex;
      justify-content: space-between;
      margin-top: 7px;
      color: #98a2b2;
      font-size: 10px;
    }

    .total {
      display: flex;
      justify-content: space-between;
      padding: 17px 23px;
      color: #748095;
      background: #fafafa;
      font-size: 12px;
    }

    .total b { color: #2a3548; }

    /* ESTILOS DE LA MATRIZ DE PERMISOS */
    .view-panel { display: block; }
    .view-panel.hidden { display: none; }

    .matrix-card {
      margin-top: 20px;
      padding: 24px;
      border: 1px solid #ece9e6;
      border-radius: 17px;
      background: #fff;
      box-shadow: 0 5px 13px rgba(31, 38, 50, .04);
    }

    .matrix-header-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
      gap: 16px;
    }

    .btn-save {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 18px;
      border: 0;
      border-radius: 10px;
      color: #fff;
      background: #8b2346;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: background .2s;
    }

    .btn-save:hover {
      background: #6d1938;
    }

    .table-responsive {
      overflow-x: auto;
    }

    .matrix-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 13px;
    }

    .matrix-table th {
      padding: 14px 16px;
      border-bottom: 2px solid #e9e5e2;
      color: #56132d;
      background: #fcfaf9;
      font-weight: 800;
      text-transform: uppercase;
      font-size: 11px;
      letter-spacing: .05em;
    }

    .matrix-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #f1efed;
      color: #354156;
    }

    .matrix-table tbody tr:hover {
      background: #faf8f7;
    }

    .matrix-table input[type="checkbox"] {
      width: 18px;
      height: 18px;
      accent-color: #8b2346;
      cursor: pointer;
    }

    .text-center { text-align: center; }

    @media (max-width: 850px) {
      .shell { grid-template-columns: 72px minmax(0, 1fr); }

      .sidebar { padding: 20px 9px; }

      .logo {
        justify-content: center;
        padding: 0 0 20px;
      }

      .logo > div:not(.logo-mark),
      .nav-title,
      .nav button span,
      .account > div:not(.avatar) {
        display: none;
      }

      .nav button {
        justify-content: center;
        padding: 11px;
      }

      .account {
        justify-content: center;
        padding: 15px 0 0;
      }

      .header,
      .content {
        padding-left: 22px;
        padding-right: 22px;
      }

      .stats { grid-template-columns: repeat(2, 1fr); }
      .quick { grid-template-columns: repeat(2, 1fr); }
      .bottom { grid-template-columns: 1fr; }
    }

    @media (max-width: 580px) {
      .shell { display: block; }
      .sidebar { display: none; }

      .header {
        height: 62px;
        padding: 0 16px;
      }

      .content { padding: 22px 16px; }
      .user-info { display: none; }

      .content h1 { font-size: 25px; }
      .welcome { font-size: 12px; }

      .period {
        align-items: flex-start;
        padding: 18px;
      }

      .period-meta { display: none; }
      .period strong { font-size: 17px; }

      .stats {
        gap: 10px;
        margin-top: 14px;
      }

      .stat { padding: 14px; }
      .stat b {
        margin-top: 12px;
        font-size: 24px;
      }

      .section {
        margin-top: 14px;
        padding: 17px;
      }

      .quick {
        gap: 8px;
        margin-top: 13px;
      }

      .action {
        min-height: 110px;
        padding: 12px 7px;
      }

      .bottom {
        gap: 14px;
        margin-top: 14px;
      }

      .event {
        grid-template-columns: 38px 1fr;
        padding: 14px 16px;
      }

      .time { display: none; }
      .panel-head { padding: 17px; }
      .career { padding: 14px 17px; }
    }
  </style>
</head>

<body>
  <div class="shell">

    <aside class="sidebar">
      <div class="logo">
        <div class="logo-mark">
          <i data-lucide="shield-check"></i>
        </div>

        <div>
          <b>TESCHA</b>
          <span>ADMINISTRACIÓN</span>
        </div>
      </div>

      <nav class="nav">
        <p class="nav-title">PANEL DE CONTROL</p>

        <button class="active" id="btn-dashboard">
          <i data-lucide="layout-dashboard"></i>
          <span>Dashboard Admin</span>
        </button>

        <p class="nav-title">ADMINISTRACIÓN SISTEMA</p>

        <button>
          <i data-lucide="users"></i>
          <span>Usuarios y Roles</span>
        </button>

        <!-- MÓDULO PERMISOS AGREGADO -->
        <button id="btn-permisos">
          <i data-lucide="shield-lock"></i>
          <span>Módulo Permisos</span>
        </button>

        <button>
          <i data-lucide="user-cog"></i>
          <span>Docentes</span>
        </button>

        <!-- JEFES DE CARRERA AGREGADO -->
        <button id="btn-jefes">
          <i data-lucide="award"></i>
          <span>Jefes de Carrera</span>
        </button>

        <!-- CONTROL ESCOLAR AGREGADO -->
        <button id="btn-control-escolar">
          <i data-lucide="graduation-cap"></i>
          <span>Control Escolar</span>
        </button>

        <button>
          <i data-lucide="settings"></i>
          <span>Configuración Sistema</span>
        </button>

        <p class="nav-title">GESTIÓN ACADÉMICA</p>

        <button>
          <i data-lucide="landmark"></i>
          <span>Carreras</span>
        </button>

        <button>
          <i data-lucide="calendar-days"></i>
          <span>Semestres / Periodos</span>
        </button>

        <button>
          <i data-lucide="book-open"></i>
          <span>Materias y Plan</span>
        </button>

        <button>
          <i data-lucide="users-round"></i>
          <span>Alumnos</span>
        </button>

        <button>
          <i data-lucide="grid-2x2"></i>
          <span>Grupos</span>
        </button>

        <p class="nav-title">REPORTES Y AUDITORÍA</p>

        <button>
          <i data-lucide="history"></i>
          <span>Bitácora / Logs</span>
        </button>

        <button>
          <i data-lucide="file-spreadsheets"></i>
          <span>Reportes General</span>
        </button>
      </nav>

      <div class="account">
        <div class="avatar admin-avatar">AD</div>

        <div>
          <b>Administrador</b>
          <span>SuperAdmin · TESCHA</span>
        </div>
      </div>
    </aside>

    <div>
      <header class="header">
        <div class="breadcrumb">
          <span>TESCHA</span>
          <i>›</i>
          <b id="breadcrumb-title">Administración General</b>
        </div>

        <div class="user">
          <span class="role-badge">SUPERADMIN</span>

          <button class="notify">
            <i data-lucide="bell"></i>
          </button>

          <div class="avatar admin-avatar">AD</div>

          <div class="user-info">
            <b>Admin Sistema</b>
            <span>Control Escolar</span>
          </div>
        </div>
      </header>

      <main class="content">

        <!-- VISTA DASHBOARD PRINCIPAL -->
        <div id="view-dashboard" class="view-panel">
          <h1>Panel de Administración General</h1>

          <p class="welcome">
            Bienvenido, Administrador del Sistema · martes, 15 de septiembre de 2026
          </p>

          <section class="period">
            <div class="period-left">
              <div class="period-icon">
                <i data-lucide="shield-alert"></i>
              </div>

              <div>
                <span class="period-label">ESTADO DEL SISTEMA</span>
                <strong>Servidores y Servicios Operativos</strong>
              </div>
            </div>

            <div class="period-meta">
              <div class="weeks">
                <b>2026-2</b>
                <span>Periodo Activo</span>
              </div>

              <span class="status">
                <i></i>
                Sistema 100% Ok
              </span>
            </div>
          </section>

          <section class="stats">
            <article class="stat">
              <div class="stat-icon burgundy">
                <i data-lucide="users"></i>
              </div>

              <b>842</b>
              <strong>Total de Usuarios</strong>
              <span>779 Alumnos · 63 Staff/Doc.</span>
            </article>

            <article class="stat">
              <div class="stat-icon blue">
                <i data-lucide="graduation-cap"></i>
              </div>

              <b>60</b>
              <strong>Docentes Activos</strong>
              <span>Planta académica asignada</span>
            </article>

            <article class="stat">
              <div class="stat-icon gold">
                <i data-lucide="landmark"></i>
              </div>

              <b>6</b>
              <strong>Carreras Registradas</strong>
              <span>Oferta educativa activa</span>
            </article>

            <article class="stat">
              <div class="stat-icon green">
                <i data-lucide="shield-check"></i>
              </div>

              <b>100%</b>
              <strong>Permisos / ROL</strong>
              <span>Acceso Total Administrador</span>
            </article>
          </section>

          <section class="section">
            <h2>Acciones rápidas de Administración</h2>

            <div class="quick">
              <button class="action">
                <i class="action-icon burgundy" data-lucide="user-plus"></i>
                <b>Crear Usuario</b>
                <span>Alta de Admin/Docente</span>
              </button>

              <button class="action">
                <i class="action-icon blue" data-lucide="calendar-plus"></i>
                <b>Nuevo Periodo</b>
                <span>Configurar ciclo escolar</span>
              </button>

              <button class="action" onclick="showPermisosView()">
                <i class="action-icon gold" data-lucide="key-round"></i>
                <b>Gestión de Roles</b>
                <span>Permisos de usuarios</span>
              </button>

              <button class="action">
                <i class="action-icon green" data-lucide="database-backup"></i>
                <b>Respaldos / Logs</b>
                <span>Ver bitácora de sistema</span>
              </button>
            </div>
          </section>

          <section class="bottom">
            <article class="feed">
              <div class="panel-head">
                <h2>Bitácora del Sistema (Actividad General)</h2>
              </div>

              <div class="event">
                <div class="event-icon burgundy">
                  <i data-lucide="key"></i>
                </div>

                <div>
                  <b>Cambio de permisos — Docente Ramírez</b>
                  <span>Asignación de rol de captura de actas</span>
                </div>

                <div class="time">Hoy 09:30<br>SuperAdmin</div>
              </div>

              <div class="event">
                <div class="event-icon gold">
                  <i data-lucide="database"></i>
                </div>

                <div>
                  <b>Cierre de Actas Parcial 3 — ISC</b>
                  <span>Proceso del sistema automatizado</span>
                </div>

                <div class="time">Hoy 08:50<br>Sistema</div>
              </div>

              <div class="event">
                <div class="event-icon blue">
                  <i data-lucide="user-check"></i>
                </div>

                <div>
                  <b>Usuario Docente Registrado — Ing. Carlos Mendoza</b>
                  <span>Departamento de Sistemas</span>
                </div>

                <div class="time">Ayer 16:22<br>SuperAdmin</div>
              </div>

              <div class="event">
                <div class="event-icon green">
                  <i data-lucide="settings-2"></i>
                </div>

                <div>
                  <b>Apertura de Periodo Escolar — 2026-2</b>
                  <span>Parámetros globales actualizados</span>
                </div>

                <div class="time">Ayer 11:30<br>SuperAdmin</div>
              </div>
            </article>

            <article class="careers">
              <div class="panel-head">
                <h2>Carreras Activas</h2>
                <a href="#">Gestionar →</a>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">IM</span>
                  <span class="career-name">Ingeniería Electromecánica</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">IE</span>
                  <span class="career-name">Ingeniería Electrónica</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">II</span>
                  <span class="career-name">Ingeniería Industrial</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">IINF</span>
                  <span class="career-name">Ingeniería Informática</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">ISC</span>
                  <span class="career-name">Ingeniería en Sistemas Computacionales</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="career">
                <div class="career-top">
                  <span class="code">IA</span>
                  <span class="career-name">Ingeniería en Administración</span>
                  <span class="arrow">›</span>
                </div>
                <div class="career-foot">
                  <span>Configuración de retícula</span>
                  <span>Editar</span>
                </div>
              </div>

              <div class="total">
                <span>Estatus global</span>
                <b>6 Carreras en Regla</b>
              </div>
            </article>
          </section>
        </div>

        <!-- VISTA MÓDULO DE PERMISOS (MATRIZ) -->
        <div id="view-permisos" class="view-panel hidden">
          <h1>Matriz de Permisos y Roles</h1>
          <p class="welcome">Configura el nivel de acceso granular por cada rol en el sistema TESCHA.</p>

          <div class="matrix-card">
            <div class="matrix-header-bar">
              <div>
                <h2 style="margin:0; font-size:18px; color:#202b3d;">Asignación de Accesos por Módulo</h2>
                <span style="font-size:12px; color:#748095;">Marca las casillas correspondientes para otorgar permisos.</span>
              </div>
              <button class="btn-save" onclick="alert('¡Permisos guardados correctamente!')">
                <i data-lucide="save"></i>
                Guardar Cambios
              </button>
            </div>

            <div class="table-responsive">
              <table class="matrix-table">
                <thead>
                  <tr>
                    <th>Módulo / Funcionalidad</th>
                    <th class="text-center">SuperAdmin</th>
                    <th class="text-center">Control Escolar</th>
                    <th class="text-center">Jefes de Carrera</th>
                    <th class="text-center">Docente</th>
                    <th class="text-center">Alumno</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><b>Gestión de Usuarios y Roles</b></td>
                    <td class="text-center"><input type="checkbox" checked disabled></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                  </tr>
                  <tr>
                    <td><b>Captura y Cierre de Actas</b></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox"></td>
                  </tr>
                  <tr>
                    <td><b>Consulta de Calificaciones / Kardex</b></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                  </tr>
                  <tr>
                    <td><b>Asignación Docente / Materias</b></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                  </tr>
                  <tr>
                    <td><b>Configuración de Periodo Escolar</b></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                  </tr>
                  <tr>
                    <td><b>Reportes y Bitácoras del Sistema</b></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox" checked></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                    <td class="text-center"><input type="checkbox"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>

      </main>
    </div>
  </div>

  <script>
    lucide.createIcons();

    const viewDashboard = document.getElementById('view-dashboard');
    const viewPermisos = document.getElementById('view-permisos');
    const breadcrumbTitle = document.getElementById('breadcrumb-title');

    const btnDashboard = document.getElementById('btn-dashboard');
    const btnPermisos = document.getElementById('btn-permisos');

    function setActiveButton(element) {
      document.querySelectorAll(".nav button").forEach((item) => {
        item.classList.remove("active");
      });
      if(element) element.classList.add("active");
    }

    function showDashboardView() {
      viewPermisos.classList.add('hidden');
      viewDashboard.classList.remove('hidden');
      breadcrumbTitle.textContent = "Administración General";
      setActiveButton(btnDashboard);
    }

    function showPermisosView() {
      viewDashboard.classList.add('hidden');
      viewPermisos.classList.remove('hidden');
      breadcrumbTitle.textContent = "Matriz de Permisos";
      setActiveButton(btnPermisos);
    }

    btnDashboard.addEventListener('click', showDashboardView);
    btnPermisos.addEventListener('click', showPermisosView);

    // Eventos generales para marcar navegación activa en los demás botones
    document.querySelectorAll(".nav button").forEach((button) => {
      button.addEventListener("click", () => {
        if(button !== btnDashboard && button !== btnPermisos) {
          setActiveButton(button);
        }
      });
    });
  </script>
</body>
</html>