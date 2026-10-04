<?php
// Turns database rows into an HTML <table> string (server-side rendering).
// The column headings come from the keys of the first row.
function render_table(array $rows): string
{
    if (empty($rows)) {
        return '<p>No data.</p>';
    }

    $columns = array_keys($rows[0]);

    $html = '<table border="1" cellpadding="6" cellspacing="0"><thead><tr>';
    foreach ($columns as $col) {
        // htmlspecialchars escapes < > & " so data can't inject HTML/JS (XSS protection).
        $html .= '<th>' . htmlspecialchars($col) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($row as $value) {
            $html .= '<td>' . htmlspecialchars((string) $value) . '</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    return $html;
}
