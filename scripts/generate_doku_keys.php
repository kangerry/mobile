<?php
$base = dirname(__DIR__, 1);
$root = realpath($base . DIRECTORY_SEPARATOR . '..');
if ($root === false) {
    $root = dirname($base, 1);
}
$target = $root . DIRECTORY_SEPARATOR . 'doku-keys';
if (!is_dir($target)) {
    mkdir($target, 0700, true);
}
$config = [
    'private_key_bits' => 2048,
    'private_key_type' => OPENSSL_KEYTYPE_RSA,
    'config' => __DIR__ . DIRECTORY_SEPARATOR . 'openssl.cnf',
];
$res = openssl_pkey_new($config);
if ($res === false) {
    $errs = [];
    while ($e = openssl_error_string()) {
        $errs[] = $e;
    }
    fwrite(STDERR, "failed to generate key\n");
    if (!empty($errs)) {
        fwrite(STDERR, implode("\n", $errs) . "\n");
    }
    exit(1);
}
$priv = '';
$ok = openssl_pkey_export($res, $priv, null, ['config' => __DIR__ . DIRECTORY_SEPARATOR . 'openssl.cnf']);
if (!$ok) {
    $errs = [];
    while ($e = openssl_error_string()) {
        $errs[] = $e;
    }
    fwrite(STDERR, "failed to export private key\n");
    if (!empty($errs)) {
        fwrite(STDERR, implode("\n", $errs) . "\n");
    }
    exit(1);
}
$details = openssl_pkey_get_details($res);
if ($details === false || !isset($details['key'])) {
    $errs = [];
    while ($e = openssl_error_string()) {
        $errs[] = $e;
    }
    fwrite(STDERR, "failed to get public key\n");
    if (!empty($errs)) {
        fwrite(STDERR, implode("\n", $errs) . "\n");
    }
    exit(1);
}
$pub = $details['key'];
$privPath = $target . DIRECTORY_SEPARATOR . 'merchant_private_key.pem';
$pubPath = $target . DIRECTORY_SEPARATOR . 'merchant_public_key.pem';
file_put_contents($privPath, $priv);
file_put_contents($pubPath, $pub);
echo $privPath . PHP_EOL;
echo $pubPath . PHP_EOL;
