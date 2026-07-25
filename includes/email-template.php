<?php

function getEmailTemplate(string $title, string $content): string
{
    $year = date('Y');

    return "
<!DOCTYPE html>
<html lang='fr'>
<head>
<meta charset='UTF-8'>
<meta name='viewport' content='width=device-width, initial-scale=1.0'>
<title>{$title}</title>
</head>

<body style='margin:0;padding:0;background:#f5f5f5;font-family:Arial,sans-serif;'>

<table width='100%' cellpadding='0' cellspacing='0' style='background:#f5f5f5;padding:40px 0;'>

<tr>
<td align='center'>

<table width='600' cellpadding='0' cellspacing='0'
style='background:#ffffff;border-radius:12px;overflow:hidden;'>

<tr>
<td
style='background:#111111;
padding:30px;
text-align:center;'>

<h1 style='color:#ffffff;margin:0;font-size:28px;'>
Below Dreams
</h1>

</td>
</tr>

<tr>

<td style='padding:40px;'>

{$content}

</td>

</tr>

<tr>

<td
style='background:#f0f0f0;
padding:20px;
text-align:center;
font-size:13px;
color:#777;'>

© {$year} Below Dreams<br>
Merci pour votre confiance.

</td>

</tr>

</table>

</td>
</tr>

</table>

</body>
</html>";
}