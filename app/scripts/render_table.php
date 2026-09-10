<?php
function render_table(array $rows): string
{
    if (empty($rows)) {
        return '<p>No data.</p>';
    }

    $columns = array_keys($rows[0]);

    $html = '<table border="1" cellpadding="6" cellspacing="0"><thead><tr>';
    foreach ($columns as $col) {
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
