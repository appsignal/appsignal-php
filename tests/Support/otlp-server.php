<?php

$host = '127.0.0.1';
$port = (int) ($argv[1] ?? 8099);

$socket = stream_socket_server("tcp://{$host}:{$port}", $errno, $errstr);

if (!$socket) {
    exit(1);
}

// @phpstan-ignore while.alwaysTrue
while (true) {
    $conn = stream_socket_accept($socket, 1);
    if ($conn === false) {
        continue;
    }

    $raw = '';
    while ($chunk = fread($conn, 8192)) {
        $raw .= $chunk;
        if (str_contains($raw, "\r\n\r\n")) {
            break;
        }
    }
    // Respond with 200.
    // In the future we might capture the output,
    // write to file and make assertions against it.
    fwrite($conn, "HTTP/1.1 200 OK\r\nContent-Length: 0\r\n\r\n");
    fclose($conn);
}
