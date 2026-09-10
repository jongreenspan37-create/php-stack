<?php
require_once __DIR__ . '/../connection.php';
$queries = require __DIR__ . '/../scripts/f1_queries.php';

$conn = db();

function query_f1_data($query)
{
    global $conn;
    $stmt = $conn->query($query);
    return $stmt->fetchAll();
}

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
</head>

<body>
    <h1>Formula 1</h1>




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
    <?= render_table($rows) ?>
</body>


</html>