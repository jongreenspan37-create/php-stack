<?php

/** HTML-escape a value before putting it in a page. */
function esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}


/** Render a minimal result page using the shared blue-palette CSS. */
function render_page(string $heading, string $body_html): void
{
    echo '<!doctype html><html lang="en"><head>'
        . '<meta charset="UTF-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1.0">'
        . '<title>' . esc($heading) . '</title>'
        . '<link rel="stylesheet" href="style.css">'
        . '<link rel="stylesheet" href="register.css">'
        . '</head><body><main class="auth-card">'
        . '<h1>' . esc($heading) . '</h1>'
        . $body_html
        . '</main></body></html>';
}
