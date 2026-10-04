<?php
// Server-rendered page: this PHP block runs on the server first, fetches the
// rows from MySQL, and the HTML below is filled in before it's sent to the browser.
// (The other pages instead load as plain HTML and fetch JSON from /api/run/ with JavaScript.)
require_once __DIR__ . '/../connection.php';
// f1_queries.php returns its array, so `require` hands back the list of queries.
$queries = require __DIR__ . '/../scripts/f1_queries.php';

// The Import button submits a form back to this page with method="post".
// $_SERVER['REQUEST_METHOD'] tells us whether this request is that form or a normal page load.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'import') {
    // create_tables.php defines import_formula_1(), the same function the API route uses.
    require_once __DIR__ . '/../scripts/create_tables.php';
    $import = import_formula_1();
    if ($import['status'] !== 'ok') {
        // Keep the full error in the server log; the page just says it failed.
        error_log('F1 import failed: ' . $import['detail']);
    }
    // Post/Redirect/Get: redirect to a normal GET so refreshing the page
    // doesn't ask to resubmit the form and run the import again.
    header('Location: formula1.php?import=' . $import['status']);
    exit;
}
// After the redirect, ?import=ok or ?import=error says what happened.
$importStatus = $_GET['import'] ?? null;

$conn = db();

// Runs one SQL query and returns all rows as arrays.
function query_f1_data($query)
{
    // Functions can't see variables from outside them unless declared global.
    global $conn;
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

// Builds an HTML <table> from the rows (same as scripts/render_table.php).
// htmlspecialchars escapes the data so it can't inject HTML/JS.
function render_table(array $data): string
{
    if (empty($data)) {
        return '<p>No data.</p>';
    }

    $columns = array_keys($data[0]);

    $html = '<table border="1" cellpadding="6" cellspacing="0"><thead><tr>';
    foreach ($columns as $col) {
        $html .= '<th>' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($data as $datarow) {
        $html .= '<tr>';
        foreach ($datarow as $value) {
            $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}

// Which query to show: the dropdown submits ?choose_query=N in the URL ($_GET).
// (int) turns it into a number; anything invalid falls back to query 0.
$queryIndex = isset($_GET['choose_query']) ? (int) $_GET['choose_query'] : 0;
if (!isset($queries[$queryIndex])) {
    $queryIndex = 0;
}
$selected = $queries[$queryIndex];
$rows = query_f1_data($selected['sql']);


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Formula 1</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: {
                preflight: false
            },
        };
    </script>
    <link rel="stylesheet" href="style.css" />
    <style>
        .result-div {
            margin: 2rem 4rem;
            font-size: large;
        }

        #table-scroll table {
            margin: 0;
        }

        #table-scroll th {
            position: sticky;
            top: 0;
            background: white;
            /* Collapsed borders don't travel with a sticky cell, so draw the line with a shadow. */
            box-shadow: inset 0 -1px 0 #999;
        }
    </style>
</head>



<body class="max-w-screen-2xl mx-auto">
    <nav>
        <ul>
            <li><a href="index.html">Main Page</a></li>
            <li><a href="list-manipulation.html">List Manipulation</a></li>
            <li><a href="database-interaction.html">Database Interaction</a></li>
            <li><a href="formula1.php">Formula 1</a></li>
            <li><a href="learning.php">Learning PHP</a></li>
        </ul>
    </nav>

    <h1>Formula 1</h1>
    <!-- A plain HTML form: clicking the button POSTs action=import to this same page,
         and the PHP at the top runs the import. No JavaScript needed. -->
    <form method="post">
        <button type="submit" name="action" value="import">Import Formula 1</button>
    </form>
    <?php if ($importStatus === 'ok'): ?>
        <p style="color: green">F1 data imported successfully.</p>
    <?php elseif ($importStatus !== null): ?>
        <p style="color: red">F1 import failed - see the server log for details.</p>
    <?php endif; ?>





    <!-- Choosing an option submits the form, which reloads the page with ?choose_query=N. -->
    <!-- The foreach/endforeach tags below loop in the HTML; the short echo tags print a value. -->
    <form method="get">
        <select name="choose_query" id="choose_query" onchange="this.form.submit()">
            <?php foreach ($queries as $index => $query): ?>

                <option value="<?= $index; ?>"
                    <?= $index === $queryIndex ? 'selected' : '' ?>>
                    <?= htmlspecialchars($query['title']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <h2><?= htmlspecialchars($selected['title']) ?></h2>
    <p><?= htmlspecialchars($selected['description']) ?></p>

    <!-- Fake scrollbar above the table; JS keeps it in sync with the real one. -->
    <div id="top-scroll" class="overflow-x-auto">
        <div id="top-scroll-inner" class="h-px"></div>
    </div>
    <!-- Capped height makes this box the vertical scroller, so the sticky header has something to stick to. -->
    <div id="table-scroll" class="overflow-auto max-h-[70vh]">
        <?= render_table($rows) ?>
    </div>

    <script>
        // Keeps the fake top scrollbar and the table's own scrollbar in sync.
        const topScroll = document.getElementById('top-scroll');
        const topInner = document.getElementById('top-scroll-inner');
        const tableScroll = document.getElementById('table-scroll');

        // Make the fake scrollbar as wide as the table so it scrolls the same distance.
        function syncWidth() {
            topInner.style.width = tableScroll.scrollWidth + 'px';
        }
        syncWidth();
        window.addEventListener('resize', syncWidth);

        topScroll.addEventListener('scroll', () => { tableScroll.scrollLeft = topScroll.scrollLeft; });
        tableScroll.addEventListener('scroll', () => { topScroll.scrollLeft = tableScroll.scrollLeft; });
    </script>
</body>


</html>