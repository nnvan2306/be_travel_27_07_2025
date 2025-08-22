<?php
// Kiểm tra Laravel requirements
echo "<h2>Laravel Requirements Check</h2>";

$required_extensions = [
    'BCMath', 'Ctype', 'cURL', 'DOM', 'Fileinfo', 'Filter', 
    'Hash', 'Mbstring', 'OpenSSL', 'PCRE', 'PDO', 'Session', 
    'Tokenizer', 'XML'
];

$recommended_extensions = [
    'GD', 'Intl', 'Zip', 'Redis'
];

echo "<h3>Required Extensions:</h3>";
foreach($required_extensions as $ext) {
    $loaded = extension_loaded(strtolower($ext));
    echo "<p>{$ext}: " . ($loaded ? '<span style="color:green">✓ Loaded</span>' : '<span style="color:red">✗ Missing</span>') . "</p>";
}

echo "<h3>Recommended Extensions:</h3>";
foreach($recommended_extensions as $ext) {
    $loaded = extension_loaded(strtolower($ext));
    echo "<p>{$ext}: " . ($loaded ? '<span style="color:green">✓ Loaded</span>' : '<span style="color:orange">○ Optional</span>') . "</p>";
}

echo "<h3>PHP Version:</h3>";
echo "<p>Current: " . PHP_VERSION . "</p>";
echo "<p>Required: 8.2+</p>";

if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo '<p style="color:green">✓ PHP Version OK</p>';
} else {
    echo '<p style="color:red">✗ PHP Version too old</p>';
}
?>
