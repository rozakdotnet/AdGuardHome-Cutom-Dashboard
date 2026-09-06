<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| AdGuard Home Dashboard -- HmLab.id
|--------------------------------------------------------------------------
| File: adguard.php --> customizable, match the fetch part -->
|   await fetch('adguard.php?api=1&_=' +
|
| AdGuard Home:
|   http://127.0.0.1:3000 ---> adjust the port, UserName and ThePassword
|   In this demo refresh every 10 seconds.
|
| Change only these credentials if required.
|--------------------------------------------------------------------------
*/

const ADGUARD_URL  = 'http://127.0.0.1:3000'; 
const ADGUARD_USER = 'UserName';
const ADGUARD_PASS = 'ThePassWord';

const REFRESH_SECONDS = 10;


/*
|--------------------------------------------------------------------------
| AdGuard API request
|--------------------------------------------------------------------------
*/

function adguardRequest(string $endpoint): array
{
    $url = rtrim(ADGUARD_URL, '/') . $endpoint;

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT        => 8,
        CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
        CURLOPT_USERPWD        => ADGUARD_USER . ':' . ADGUARD_PASS,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json'
        ],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response = curl_exec($ch);
    $error    = curl_error($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || $error !== '') {
        return [
            'ok'    => false,
            'error' => $error ?: 'Connection failed'
        ];
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        return [
            'ok'    => false,
            'error' => 'Invalid JSON response',
            'http'  => $httpCode
        ];
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        return [
            'ok'    => false,
            'error' => 'HTTP ' . $httpCode,
            'http'  => $httpCode,
            'data'  => $data
        ];
    }

    return [
        'ok'   => true,
        'data' => $data
    ];
}


/*
|--------------------------------------------------------------------------
| API endpoint for JavaScript
|--------------------------------------------------------------------------
*/

if (isset($_GET['api'])) {

    header('Content-Type: application/json; charset=utf-8');

    $status  = adguardRequest('/control/status');
    $stats   = adguardRequest('/control/stats');
    $clients = adguardRequest('/control/clients');

    echo json_encode([
        'success' => true,
        'time'    => time(),

        'status' => [
            'ok'    => $status['ok'],
            'data'  => $status['ok'] ? $status['data'] : null,
            'error' => $status['ok'] ? null : $status['error']
        ],

        'stats' => [
            'ok'    => $stats['ok'],
            'data'  => $stats['ok'] ? $stats['data'] : null,
            'error' => $stats['ok'] ? null : $stats['error']
        ],

        'clients' => [
            'ok'    => $clients['ok'],
            'data'  => $clients['ok'] ? $clients['data'] : null,
            'error' => $clients['ok'] ? null : $clients['error']
        ]

    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    exit;
}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
name="viewport"
content="width=device-width, initial-scale=1.0">

<title>AdGuard Home Dashboard</title>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

<style>

/* =========================================================
   ROOT
   ========================================================= */

:root {

    --bg: #0b0f14;
    --panel: #111720;
    --panel-hover: #151d28;

    --border: rgba(255,255,255,.075);

    --text: #edf2f7;
    --muted: #8995a4;

    --green: #35d07f;
    --red: #ff5c69;
    --blue: #5ca8ff;
    --yellow: #f2c766;
    --purple: #a78bfa;

    --radius: 16px;
}


/* =========================================================
   RESET
   ========================================================= */

* {
    box-sizing: border-box;
}

html,
body {
    margin: 0;
    padding: 0;
}

body {

    background: var(--bg);

    color: var(--text);

    font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    min-height: 100vh;
}


/* =========================================================
   PAGE
   ========================================================= */

.page {

    width: 100%;

    max-width: 1500px;

    margin: 0 auto;

    padding: 24px;
}


/* =========================================================
   HEADER
   ========================================================= */

.header {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    margin-bottom: 20px;
}

.header-left {

    display: flex;

    align-items: center;

    gap: 13px;
}

.logo {

    width: 48px;
    height: 48px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 14px;

    background:
        linear-gradient(
            135deg,
            #1677ff,
            #56b4ff
        );

    box-shadow:
        0 8px 30px rgba(0,120,255,.20);
}

.logo svg {

    width: 25px;
    height: 25px;

    fill: none;

    stroke: white;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}

.title {

    font-size: 22px;

    font-weight: 700;

    letter-spacing: -.4px;
}

.subtitle {

    margin-top: 3px;

    color: var(--muted);

    font-size: 13px;
}


/* =========================================================
   ONLINE STATUS
   ========================================================= */

.header-status {

    display: flex;

    align-items: center;

    gap: 8px;

    padding:
        8px
        12px;

    border:
        1px solid var(--border);

    border-radius: 999px;

    background:
        rgba(255,255,255,.035);

    font-size: 12px;

    white-space: nowrap;
}

.status-dot {

    width: 8px;
    height: 8px;

    border-radius: 50%;

    background: var(--green);

    box-shadow:
        0 0 10px rgba(53,208,127,.75);
}

.status-dot.offline {

    background: var(--red);

    box-shadow:
        0 0 10px rgba(255,92,105,.75);
}


/* =========================================================
   STAT GRID
   ========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, minmax(0, 1fr));

    gap: 14px;

    margin-bottom: 14px;
}


/* =========================================================
   CARD
   ========================================================= */

.card {

    background: var(--panel);

    border:
        1px solid var(--border);

    border-radius: var(--radius);

    padding: 18px;

    box-shadow:
        0 8px 28px rgba(0,0,0,.13);

    transition:
        background .2s ease,
        border-color .2s ease;
}

.card:hover {

    background: var(--panel-hover);

    border-color:
        rgba(255,255,255,.10);
}


/* =========================================================
   STAT CARD
   ========================================================= */

.stat-card {

    min-height: 125px;

    display: flex;

    flex-direction: column;

    justify-content: space-between;
}

.card-label {

    display: flex;

    align-items: center;

    justify-content: space-between;

    color: var(--muted);

    font-size: 13px;
}

.card-icon {

    width: 30px;
    height: 30px;

    display: flex;

    align-items: center;
    justify-content: center;

    border-radius: 9px;

    background:
        rgba(255,255,255,.045);
}

.card-icon svg {

    width: 17px;
    height: 17px;

    fill: none;

    stroke: currentColor;

    stroke-width: 1.8;

    stroke-linecap: round;

    stroke-linejoin: round;
}

.card-value {

    font-size: 28px;

    line-height: 1;

    font-weight: 700;

    letter-spacing: -.8px;
}

.card-small {

    color: var(--muted);

    font-size: 11px;
}


/* =========================================================
   MAIN GRID
   ========================================================= */

.main-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 2fr)
        minmax(320px, 1fr);

    gap: 14px;

    margin-bottom: 14px;
}


/* =========================================================
   CARD HEADER
   ========================================================= */

.card-header {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 15px;

    margin-bottom: 16px;
}

.card-title {

    font-size: 15px;

    font-weight: 650;
}

.card-description {

    color: var(--muted);

    font-size: 11px;

    margin-top: 3px;
}


/* =========================================================
   CHART
   ========================================================= */

.chart-wrap {

    position: relative;

    width: 100%;

    height: 320px;
}


/* =========================================================
   INFO
   ========================================================= */

.info-list {

    display: flex;

    flex-direction: column;

    gap: 0;
}

.info-row {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    min-height: 43px;

    border-bottom:
        1px solid var(--border);
}

.info-row:last-child {

    border-bottom: 0;
}

.info-name {

    color: var(--muted);

    font-size: 12px;
}

.info-value {

    max-width: 65%;

    text-align: right;

    font-size: 12px;

    font-weight: 600;

    word-break: break-word;
}

.good {

    color: var(--green);
}

.bad {

    color: var(--red);
}

.warn {

    color: var(--yellow);
}


/* =========================================================
   BOTTOM GRID
   ========================================================= */

.bottom-grid {

    display: grid;

    grid-template-columns:
        repeat(2, minmax(0, 1fr));

    gap: 14px;
}


/* =========================================================
   TABLE
   ========================================================= */

.table {

    width: 100%;

    border-collapse: collapse;

    table-layout: fixed;
}

.table th {

    padding:
        0
        0
        9px;

    color: var(--muted);

    font-size: 10px;

    font-weight: 500;

    text-align: left;

    text-transform: uppercase;

    letter-spacing: .5px;
}

.table th:last-child,
.table td:last-child {

    width: 85px;

    text-align: right;
}

.table td {

    padding:
        10px
        0;

    border-top:
        1px solid var(--border);

    font-size: 12px;
}

.domain {

    overflow: hidden;

    white-space: nowrap;

    text-overflow: ellipsis;
}

.value-number {

    font-weight: 650;
}

.bar {

    width: 100%;

    height: 4px;

    margin-top: 6px;

    overflow: hidden;

    border-radius: 99px;

    background:
        rgba(255,255,255,.055);
}

.bar span {

    display: block;

    width: 0;

    height: 100%;

    border-radius: inherit;

    background: var(--blue);

    transition:
        width .35s ease;
}


/* =========================================================
   UPSTREAM
   ========================================================= */

.upstream-name {

    overflow: hidden;

    white-space: nowrap;

    text-overflow: ellipsis;

    max-width: 330px;
}

.upstream-time {

    color: var(--muted);

    font-size: 11px;
}


/* =========================================================
   FOOTER
   ========================================================= */

.footer {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;

    padding-top: 14px;

    color: var(--muted);

    font-size: 10px;
}

.refresh {

    display: flex;

    align-items: center;

    gap: 6px;
}


/* =========================================================
   ERROR
   ========================================================= */

.connection-error {

    display: none;

    margin-bottom: 14px;

    padding: 12px 14px;

    border:
        1px solid rgba(255,92,105,.2);

    border-radius: 12px;

    background:
        rgba(255,92,105,.06);

    color: var(--red);

    font-size: 12px;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 1050px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, minmax(0, 1fr));
    }

    .main-grid {

        grid-template-columns: 1fr;
    }
}


@media (max-width: 700px) {

    .page {

        padding: 14px;
    }

    .header {

        align-items: flex-start;
    }

    .title {

        font-size: 18px;
    }

    .subtitle {

        font-size: 11px;
    }

    .logo {

        width: 42px;
        height: 42px;
    }

    .stats-grid {

        gap: 10px;
    }

    .card {

        padding: 14px;

        border-radius: 14px;
    }

    .card-value {

        font-size: 23px;
    }

    .chart-wrap {

        height: 245px;
    }

    .bottom-grid {

        grid-template-columns: 1fr;
    }

    .upstream-name {

        max-width: 210px;
    }
}


@media (max-width: 430px) {

    .header-status {

        padding:
            7px 9px;

        font-size: 10px;
    }

    .stats-grid {

        grid-template-columns: 1fr 1fr;
    }

    .stat-card {

        min-height: 108px;
    }

    .card-small {

        font-size: 10px;
    }

    .card-value {

        font-size: 21px;
    }
}

</style>

</head>

<body>

<div class="page">

<!-- =====================================================
HEADER
===================================================== -->

<header class="header">

    <div class="header-left">

        <div class="logo">

            <svg viewBox="0 0 24 24">

                <path
                    d="M12 3
                       19 6
                       v5
                       c0 4.6-2.9 8.2-7 10
                       -4.1-1.8-7-5.4-7-10V6z"
                />

                <path d="m9 12 2 2 4-4"/>

            </svg>

        </div>


        <div>

            <div class="title">
                AdGuard Home
            </div>

            <div class="subtitle">
                DNS &amp; network protection
            </div>

        </div>

    </div>


    <div class="header-status">

        <span
            id="statusDot"
            class="status-dot"
        ></span>

        <span id="statusText">
            Connecting...
        </span>

    </div>

</header>


<div
    id="connectionError"
    class="connection-error"
></div>


<!-- =====================================================
     MAIN STATISTICS
     ===================================================== -->

<section class="stats-grid">


    <!-- TOTAL -->

    <div class="card stat-card">

        <div class="card-label">

            <span>
                Total Queries
            </span>

            <span class="card-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="8"
                    />

                    <path d="M12 8v4l3 2"/>

                </svg>

            </span>

        </div>

        <div
            id="totalQueries"
            class="card-value"
        >
            —
        </div>

        <div class="card-small">
            DNS requests
        </div>

    </div>


    <!-- BLOCKED -->

    <div class="card stat-card">

        <div class="card-label">

            <span>
                Blocked
            </span>

            <span class="card-icon">

                <svg viewBox="0 0 24 24">

                    <circle
                        cx="12"
                        cy="12"
                        r="8"
                    />

                    <path d="m8 8 8 8"/>

                </svg>

            </span>

        </div>

        <div
            id="blockedQueries"
            class="card-value"
        >
            —
        </div>

        <div class="card-small">
            filtered requests
        </div>

    </div>


    <!-- BLOCK RATE -->

    <div class="card stat-card">

        <div class="card-label">

            <span>
                Block Rate
            </span>

            <span class="card-icon">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M12 3
                           19 6
                           v5
                           c0 4.6-2.9 8.2-7 10
                           -4.1-1.8-7-5.4-7-10V6z"
                    />

                    <path d="m9 12 2 2 4-4"/>

                </svg>

            </span>

        </div>

        <div
            id="blockRate"
            class="card-value"
        >
            —
        </div>

        <div class="card-small">
            of all queries
        </div>

    </div>


    <!-- CLIENTS -->

    <div class="card stat-card">

        <div class="card-label">

            <span>
                Active Clients
            </span>

            <span class="card-icon">

                <svg viewBox="0 0 24 24">

                    <path
                        d="M16 20
                           v-1.5
                           a3.5 3.5 0 0 0-3.5-3.5h-5
                           A3.5 3.5 0 0 0 4 18.5V20"
                    />

                    <circle
                        cx="10"
                        cy="7"
                        r="3"
                    />

                    <path
                        d="M16 11
                           a3 3 0 0 1 3 3
                           v1"
                    />

                </svg>

            </span>

        </div>

        <div
            id="activeClients"
            class="card-value"
        >
            —
        </div>

        <div class="card-small">
            discovered clients
        </div>

    </div>

</section>


<!-- =====================================================
     CHART + SERVER STATUS
     ===================================================== -->

<section class="main-grid">


    <!-- CHART -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    DNS Activity
                </div>

                <div class="card-description">
                    Queries and blocked requests
                </div>

            </div>

        </div>


        <div class="chart-wrap">

            <canvas id="queryChart"></canvas>

        </div>

    </div>


    <!-- STATUS -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    AdGuard Status
                </div>

                <div class="card-description">
                    Server information
                </div>

            </div>

        </div>


        <div class="info-list">


            <div class="info-row">

                <span class="info-name">
                    Version
                </span>

                <span
                    id="version"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Protection
                </span>

                <span
                    id="protection"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    DNS Port
                </span>

                <span
                    id="dnsPort"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    HTTP Port
                </span>

                <span
                    id="httpPort"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Running
                </span>

                <span
                    id="running"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Processing
                </span>

                <span
                    id="processingTime"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Last Update
                </span>

                <span
                    id="lastUpdate"
                    class="info-value"
                >
                    —
                </span>

            </div>


        </div>

    </div>

</section>


<!-- =====================================================
     TABLES
     ===================================================== -->

<section class="bottom-grid">


    <!-- TOP CLIENTS -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    Top Clients
                </div>

                <div class="card-description">
                    DNS requests by client
                </div>

            </div>

        </div>


        <table class="table">

            <thead>

                <tr>

                    <th>
                        Client
                    </th>

                    <th>
                        Requests
                    </th>

                </tr>

            </thead>

            <tbody id="clientsTable">

                <tr>

                    <td
                        colspan="2"
                        style="color:var(--muted)"
                    >
                        Loading...
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <!-- TOP QUERIED -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    Top Queried Domains
                </div>

                <div class="card-description">
                    Most requested domains
                </div>

            </div>

        </div>


        <table class="table">

            <thead>

                <tr>

                    <th>
                        Domain
                    </th>

                    <th>
                        Queries
                    </th>

                </tr>

            </thead>

            <tbody id="domainsTable">

                <tr>

                    <td
                        colspan="2"
                        style="color:var(--muted)"
                    >
                        Loading...
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <!-- TOP BLOCKED -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    Top Blocked Domains
                </div>

                <div class="card-description">
                    Domains blocked by filtering
                </div>

            </div>

        </div>


        <table class="table">

            <thead>

                <tr>

                    <th>
                        Domain
                    </th>

                    <th>
                        Blocked
                    </th>

                </tr>

            </thead>

            <tbody id="blockedTable">

                <tr>

                    <td
                        colspan="2"
                        style="color:var(--muted)"
                    >
                        Loading...
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <!-- UPSTREAMS -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    DNS Upstreams
                </div>

                <div class="card-description">
                    Upstream response statistics
                </div>

            </div>

        </div>


        <table class="table">

            <thead>

                <tr>

                    <th>
                        Upstream
                    </th>

                    <th>
                        Responses
                    </th>

                </tr>

            </thead>

            <tbody id="upstreamsTable">

                <tr>

                    <td
                        colspan="2"
                        style="color:var(--muted)"
                    >
                        Loading...
                    </td>

                </tr>

            </tbody>

        </table>

    </div>


    <!-- PROTECTION -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    Protection Services
                </div>

                <div class="card-description">
                    Current filtering services
                </div>

            </div>

        </div>


        <div class="info-list">


            <div class="info-row">

                <span class="info-name">
                    Filtering
                </span>

                <span
                    id="filtering"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Safe Browsing
                </span>

                <span
                    id="safeBrowsing"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Safe Search
                </span>

                <span
                    id="safeSearch"
                    class="info-value"
                >
                    —
                </span>

            </div>


            <div class="info-row">

                <span class="info-name">
                    Parental Control
                </span>

                <span
                    id="parental"
                    class="info-value"
                >
                    —
                </span>

            </div>


        </div>

    </div>


    <!-- DNS ADDRESSES -->

    <div class="card">

        <div class="card-header">

            <div>

                <div class="card-title">
                    DNS Addresses
                </div>

                <div class="card-description">
                    Addresses exposed by AdGuard
                </div>

            </div>

        </div>


        <div
            id="dnsAddresses"
            class="info-list"
        >

            <div class="info-row">

                <span class="info-name">
                    Loading
                </span>

                <span class="info-value">
                    —
                </span>

            </div>

        </div>

    </div>


</section>


<!-- =====================================================
     FOOTER
     ===================================================== -->

<footer class="footer">

    <div>
        AdGuard Home Dashboard
    </div>

    <div class="refresh">

        <span>
            Auto refresh
        </span>

        <strong id="refreshText">
            10s
        </strong>

        <span>
            ·
        </span>

        <span id="updatedText">
            —
        </span>

    </div>

</footer>

</div>

<script>

/* =========================================================
   GLOBAL
   ========================================================= */

let queryChart = null;


/* =========================================================
   HELPERS
   ========================================================= */

function escapeHtml(value) {

    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}


function numberFormat(value) {

    return Number(value || 0)
        .toLocaleString();
}


function setText(id, value) {

    const element =
        document.getElementById(id);

    if (element) {
        element.textContent = value;
    }
}


function setStatusValue(
    id,
    enabled,
    onText = 'Enabled',
    offText = 'Disabled'
) {

    const element =
        document.getElementById(id);

    if (!element) {
        return;
    }

    element.textContent =
        enabled
            ? onText
            : offText;

    element.classList.remove(
        'good',
        'bad',
        'warn'
    );

    element.classList.add(
        enabled
            ? 'good'
            : 'bad'
    );
}


/* =========================================================
   HEADER STATUS
   ========================================================= */

function setOnline(online) {

    const dot =
        document.getElementById('statusDot');

    const text =
        document.getElementById('statusText');


    if (online) {

        dot.classList.remove(
            'offline'
        );

        text.textContent =
            'AdGuard Online';

    } else {

        dot.classList.add(
            'offline'
        );

        text.textContent =
            'AdGuard Offline';
    }
}


/* =========================================================
   CHART
   ========================================================= */

function initChart() {

    const canvas =
        document.getElementById(
            'queryChart'
        );


    queryChart =
        new Chart(
            canvas.getContext('2d'),
            {

                type: 'line',

                data: {

                    labels: [],

                    datasets: [

                        {
                            label: 'Queries',

                            data: [],

                            borderWidth: 2,

                            pointRadius: 0,

                            tension: .35,

                            fill: true
                        },

                        {
                            label: 'Blocked',

                            data: [],

                            borderWidth: 2,

                            pointRadius: 0,

                            tension: .35,

                            fill: false
                        }

                    ]
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },

                    plugins: {

                        legend: {

                            position: 'top',

                            align: 'end',

                            labels: {
                                usePointStyle: true,
                                boxWidth: 8
                            }
                        },

                        tooltip: {

                            callbacks: {

                                label:
                                    function(context) {

                                        return (
                                            ' ' +
                                            context.dataset.label +
                                            ': ' +
                                            numberFormat(
                                                context.raw
                                            )
                                        );
                                    }
                            }
                        }
                    },

                    scales: {

                        x: {

                            grid: {
                                display: false
                            },

                            ticks: {
                                color: '#8995a4',
                                maxTicksLimit: 8
                            }
                        },

                        y: {

                            beginAtZero: true,

                            grid: {
                                color:
                                    'rgba(255,255,255,.05)'
                            },

                            ticks: {
                                color: '#8995a4'
                            }
                        }
                    }
                }
            }
        );
}


/* =========================================================
   CHART UPDATE
   ========================================================= */

function updateChart(stats) {

    if (!queryChart) {
        return;
    }


    const queries =
        Array.isArray(stats.dns_queries)
            ? stats.dns_queries
            : [];


    const blocked =
        Array.isArray(stats.blocked_filtering)
            ? stats.blocked_filtering
            : [];


    const unit =
        stats.time_units ||
        'hours';


    const labels =
        queries.map(
            (_, index) => {

                if (unit === 'hours') {

                    const hour =
                        queries.length -
                        1 -
                        index;

                    return (
                        hour === 0
                            ? 'Now'
                            : '-' + hour + 'h'
                    );
                }

                return String(
                    index + 1
                );
            }
        );


    queryChart.data.labels =
        labels;


    queryChart.data.datasets[0].data =
        queries;


    queryChart.data.datasets[1].data =
        blocked;


    queryChart.update('none');
}


/* =========================================================
   ARRAY OF {name:value}
   ========================================================= */

function objectArrayToList(data) {

    if (!Array.isArray(data)) {
        return [];
    }


    const result = [];


    data.forEach(item => {

        if (
            item &&
            typeof item === 'object'
        ) {

            Object.entries(item)
                .forEach(
                    ([name, value]) => {

                        result.push({
                            name,
                            value:
                                Number(value) || 0
                        });

                    }
                );
        }

    });


    return result;
}


/* =========================================================
   GENERIC TABLE
   ========================================================= */

function renderTable(
    elementId,
    data,
    emptyText = 'No data'
) {

    const tbody =
        document.getElementById(
            elementId
        );


    if (!tbody) {
        return;
    }


    if (
        !Array.isArray(data) ||
        data.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td colspan="2"
                    style="color:var(--muted)">
                    ${escapeHtml(emptyText)}
                </td>
            </tr>
        `;

        return;
    }


    const sorted =
        [...data].sort(
            (a,b) =>
                Number(b.value || 0) -
                Number(a.value || 0)
        );


    const top =
        sorted.slice(0, 8);


    const max =
        Math.max(
            ...top.map(
                item =>
                    Number(item.value || 0)
            )
        );


    tbody.innerHTML =
        top.map(item => {

            const value =
                Number(item.value || 0);


            const width =
                max > 0
                    ? (
                        value /
                        max *
                        100
                    )
                    : 0;


            return `
                <tr>

                    <td>

                        <div class="domain"
                             title="${escapeHtml(item.name)}">

                            ${escapeHtml(item.name)}

                        </div>

                        <div class="bar">

                            <span
                                style="width:${width}%"
                            ></span>

                        </div>

                    </td>

                    <td class="value-number">

                        ${numberFormat(value)}

                    </td>

                </tr>
            `;

        }).join('');
}


/* =========================================================
   UPSTREAM TABLE
   ========================================================= */

function renderUpstreams(
    responseData,
    avgData
) {

    const tbody =
        document.getElementById(
            'upstreamsTable'
        );


    const responses =
        objectArrayToList(
            responseData
        );


    const averages =
        objectArrayToList(
            avgData
        );


    if (
        responses.length === 0
    ) {

        tbody.innerHTML = `
            <tr>
                <td colspan="2"
                    style="color:var(--muted)">
                    No upstream data
                </td>
            </tr>
        `;

        return;
    }


    const avgMap = {};


    averages.forEach(item => {

        avgMap[item.name] =
            Number(item.value || 0);

    });


    tbody.innerHTML =
        responses
            .sort(
                (a,b) =>
                    b.value -
                    a.value
            )
            .slice(0, 8)
            .map(item => {

                const avg =
                    avgMap[item.name];


                const avgText =
                    avg !== undefined
                        ? (
                            ' · ' +
                            (
                                avg * 1000
                            ).toFixed(1) +
                            ' ms avg'
                        )
                        : '';


                return `
                    <tr>

                        <td>

                            <div
                                class="upstream-name"
                                title="${escapeHtml(item.name)}"
                            >
                                ${escapeHtml(item.name)}
                            </div>

                            <div class="upstream-time">
                                ${avgText}
                            </div>

                        </td>

                        <td class="value-number">

                            ${numberFormat(item.value)}

                        </td>

                    </tr>
                `;

            })
            .join('');
}


/* =========================================================
   DNS ADDRESSES
   ========================================================= */

function renderDnsAddresses(
    addresses
) {

    const container =
        document.getElementById(
            'dnsAddresses'
        );


    if (
        !Array.isArray(addresses) ||
        addresses.length === 0
    ) {

        container.innerHTML = `
            <div class="info-row">

                <span class="info-name">
                    Address
                </span>

                <span class="info-value">
                    —
                </span>

            </div>
        `;

        return;
    }


    container.innerHTML =
        addresses
            .slice(0, 8)
            .map(address => {

                return `
                    <div class="info-row">

                        <span class="info-name">
                            DNS
                        </span>

                        <span
                            class="info-value"
                            title="${escapeHtml(address)}"
                        >
                            ${escapeHtml(address)}
                        </span>

                    </div>
                `;

            })
            .join('');
}


/* =========================================================
   LOAD DATA
   ========================================================= */

async function loadData() {

    const errorBox =
        document.getElementById(
            'connectionError'
        );


    try {

        const response =
            await fetch(
                'adh.php?api=1&_=' +
                Date.now(),
                {
                    cache: 'no-store'
                }
            );


        if (!response.ok) {

            throw new Error(
                'HTTP ' +
                response.status
            );
        }


        const json =
            await response.json();


        if (!json.success) {

            throw new Error(
                'API returned error'
            );
        }


        errorBox.style.display =
            'none';


        const status =
            json.status.data || {};


        const stats =
            json.stats.data || {};


        const clients =
            json.clients.data || {};


        setOnline(
            Boolean(
                json.status.ok &&
                json.stats.ok
            )
        );


        /* =================================================
           STATUS
           ================================================= */

        setText(
            'version',
            status.version || '—'
        );


        setStatusValue(
            'protection',
            Boolean(
                status.protection_enabled
            )
        );


        setText(
            'dnsPort',
            status.dns_port ??
            '—'
        );


        setText(
            'httpPort',
            status.http_port ??
            '—'
        );


        setStatusValue(
            'running',
            Boolean(status.running),
            'Running',
            'Stopped'
        );


        /* =================================================
           STATISTICS
           ================================================= */

        const total =
            Number(
                stats.num_dns_queries ||
                0
            );


        const blocked =
            Number(
                stats.num_blocked_filtering ||
                0
            );


        const blockRate =
            total > 0
                ? (
                    blocked /
                    total *
                    100
                )
                : 0;


        setText(
            'totalQueries',
            numberFormat(total)
        );


        setText(
            'blockedQueries',
            numberFormat(blocked)
        );


        setText(
            'blockRate',
            blockRate.toFixed(1) + '%'
        );


        const processing =
            Number(
                stats.avg_processing_time ||
                0
            );


        setText(
            'processingTime',
            (
                processing *
                1000
            ).toFixed(2) +
            ' ms'
        );


        /* =================================================
           CHART
           ================================================= */

        updateChart(stats);


        /* =================================================
           TOP CLIENTS
           ================================================= */

        /*
         * IMPORTANT:
         *
         * AdGuard response:
         *
         * top_clients: [
         *   {"192.168.0.2":67}
         * ]
         *
         * NOT:
         *
         * {"192.168.0.2":67}
         */

        const topClients =
            objectArrayToList(
                stats.top_clients
            );


        renderTable(
            'clientsTable',
            topClients,
            'No client data'
        );


        /* =================================================
           ACTIVE CLIENTS
           ================================================= */

        const autoClients =
            Array.isArray(
                clients.auto_clients
            )
                ? clients.auto_clients
                : [];


        const configuredClients =
            Array.isArray(
                clients.clients
            )
                ? clients.clients
                : [];


        const activeCount =
            autoClients.length ||
            configuredClients.length ||
            topClients.length;


        setText(
            'activeClients',
            numberFormat(activeCount)
        );


        /* =================================================
           TOP QUERIED DOMAINS
           ================================================= */

        const topQueried =
            objectArrayToList(
                stats.top_queried_domains
            );


        renderTable(
            'domainsTable',
            topQueried,
            'No domain data'
        );


        /* =================================================
           TOP BLOCKED DOMAINS
           ================================================= */

        const topBlocked =
            objectArrayToList(
                stats.top_blocked_domains
            );


        renderTable(
            'blockedTable',
            topBlocked,
            'No blocked domains'
        );


        /* =================================================
           UPSTREAMS
           ================================================= */

        renderUpstreams(
            stats.top_upstreams_responses,
            stats.top_upstreams_avg_time
        );


        /* =================================================
           PROTECTION SERVICES
           ================================================= */

        /*
         * The stats endpoint tells us the replacement
         * counters, but not directly whether each
         * feature is enabled.
         *
         * We therefore show:
         *
         * Filtering = Active when blocked filtering
         * data exists.
         *
         * Safe Browsing / Safe Search / Parental =
         * Enabled when the corresponding replacement
         * statistic exists in the API response.
         */


        const filtering =
            stats.num_blocked_filtering !== undefined;


        const safeBrowsing =
            stats.num_replaced_safebrowsing !== undefined;


        const safeSearch =
            stats.num_replaced_safesearch !== undefined;


        const parental =
            stats.num_replaced_parental !== undefined;


        setStatusValue(
            'filtering',
            filtering,
            'Available',
            'Unavailable'
        );


        setStatusValue(
            'safeBrowsing',
            safeBrowsing,
            'Available',
            'Unavailable'
        );


        setStatusValue(
            'safeSearch',
            safeSearch,
            'Available',
            'Unavailable'
        );


        setStatusValue(
            'parental',
            parental,
            'Available',
            'Unavailable'
        );


        /* =================================================
           DNS ADDRESSES
           ================================================= */

        renderDnsAddresses(
            status.dns_addresses
        );


        /* =================================================
           TIME
           ================================================= */

        const now =
            new Date();


        const time =
            now.toLocaleTimeString();


        setText(
            'lastUpdate',
            time
        );


        setText(
            'updatedText',
            'Updated ' +
            time
        );


    } catch (error) {

        console.error(
            'AdGuard Dashboard:',
            error
        );


        setOnline(false);


        errorBox.textContent =
            'Unable to connect to AdGuard Home: ' +
            error.message;


        errorBox.style.display =
            'block';


        setText(
            'updatedText',
            'Connection failed'
        );
    }
}


/* =========================================================
   START
   ========================================================= */

document.addEventListener(
    'DOMContentLoaded',
    function() {

        initChart();

        loadData();

        setInterval(
            loadData,
            <?php echo (int) REFRESH_SECONDS; ?> * 1000
        );

    }
);

</script>

</body>
</html>
