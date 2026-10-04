<?php
// Two tiny endpoints for checking the router works: no input, fixed JSON output.
// Wired to the Test buttons on database-interaction.html.

function test_1($body = null)
{
    return ['result1' => 'success'];
}

function test_2($body = null)
{
    return ['result2' => 'success'];
}
