<?php
declare(strict_types=1);

final class HttpClient
{
    private string $baseUrl;
    private string $cookieFile;
    private bool $loggedIn = false;

    public function __construct(string $baseUrl)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cookieFile = sys_get_temp_dir() . '/mtl-admin-cookies-' . bin2hex(random_bytes(4)) . '.txt';
    }

    public function __destruct()
    {
        if (is_file($this->cookieFile)) {
            unlink($this->cookieFile);
        }
    }

    public function ping(): bool
    {
        $res = $this->request('GET', '/index.html');
        return $res['code'] >= 200 && $res['code'] < 500;
    }

    public function login(string $user = 'admin', string $pass = 'password'): void
    {
        $res = $this->request('POST', '/admin/index.php', [
            'username' => $user,
            'password' => $pass,
        ]);
        if ($res['code'] >= 400) {
            throw new RuntimeException('เข้าสู่ระบบไม่สำเร็จ HTTP ' . $res['code']);
        }
        $this->loggedIn = true;
    }

    public function isLoggedIn(): bool
    {
        return $this->loggedIn;
    }

    public function getAdminPage(string $path): array
    {
        return $this->request('GET', '/admin/' . ltrim($path, '/'));
    }

    public function postAdmin(string $path, array $fields): array
    {
        return $this->request('POST', '/admin/' . ltrim($path, '/'), $fields);
    }

    public function postJson(string $path, array $payload): array
    {
        return $this->request('POST', $path, json_encode($payload, JSON_UNESCAPED_UNICODE), [
            'Content-Type: application/json',
        ]);
    }

    public function extractCsrf(string $html): string
    {
        if (preg_match('/name="csrf"\s+value="([^"]+)"/', $html, $m)) {
            return $m[1];
        }
        throw new RuntimeException('ไม่พบ CSRF token ในหน้า');
    }

    public function request(string $method, string $path, $body = null, array $extraHeaders = []): array
    {
        $url = str_starts_with($path, 'http') ? $path : $this->baseUrl . $path;
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('curl_init ล้มเหลว');
        }

        $headers = $extraHeaders;
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_COOKIEJAR => $this->cookieFile,
            CURLOPT_COOKIEFILE => $this->cookieFile,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($method === 'POST' && $body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $bodyOut = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($bodyOut === false) {
            throw new RuntimeException('HTTP error: ' . $err);
        }

        return ['code' => $code, 'body' => (string) $bodyOut];
    }
}
