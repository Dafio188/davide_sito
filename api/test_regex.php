<?php
$text_clean = '```json
{"isoDate": "2026-01-15T11:00:00+01:00"}
```';

$text_dirty = 'Here is the JSON:
```json
{"isoDate": "2026-01-15T11:00:00+01:00"}
```';

$text_no_markdown = '{"isoDate": "2026-01-15T11:00:00+01:00"}';

function clean($text) {
    return preg_replace('/^```json\s*|\s*```$/', '', trim($text));
}

echo "Clean: " . clean($text_clean) . "\n";
echo "Dirty: " . clean($text_dirty) . "\n";
echo "No Markdown: " . clean($text_no_markdown) . "\n";

echo "--- JSON DECODE CHECK ---\n";
echo "Dirty Decode: " . (json_decode(clean($text_dirty)) ? "OK" : "FAIL") . "\n";
?>
