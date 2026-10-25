<?php
declare(strict_types=1);

namespace Malia\Tests\Integration;

/**
 * Minimaler HTTP-Client für die Integrationstests. Er verhält sich wie ein Browser:
 * Er merkt sich das Session-Cookie, folgt aber NICHT automatisch Weiterleitungen,
 * damit wir den Statuscode (302/403) und das Ziel (Location) prüfen können.
 */
class HttpClient
{
    /** @var array<string,string> Cookies (Name => Wert) */
    private array $cookies = [];

    public function __construct(private string $baseUrl)
    {
    }

    /** @return array{status:int, location:?string, body:string} */
    public function get(string $path): array
    {
        return $this->request('GET', $path, null);
    }

    /** @return array{status:int, location:?string, body:string} */
    public function post(string $path, array $data): array
    {
        return $this->request('POST', $path, $data);
    }

    /** Holt das CSRF-Token aus dem HTML einer Seite (hidden-Feld "csrf_token"). */
    public function csrfFrom(string $path): string
    {
        $body = $this->get($path)['body'];
        preg_match('/name="csrf_token" value="([0-9a-f]+)"/', $body, $m);
        return $m[1] ?? '';
    }

    /**
     * Meldet an wie im Browser (erst Formular holen wegen CSRF, dann absenden).
     * Gibt die Antwort des POST zurück.
     */
    public function login(string $username, string $password): array
    {
        $token = $this->csrfFrom('login.php');
        return $this->post('login.php', [
            'username'   => $username,
            'password'   => $password,
            'csrf_token' => $token,
        ]);
    }

    private function request(string $method, string $path, ?array $data): array
    {
        $headers = [];
        if ($this->cookies) {
            $pairs = [];
            foreach ($this->cookies as $k => $v) {
                $pairs[] = $k . '=' . $v;
            }
            $headers[] = 'Cookie: ' . implode('; ', $pairs);
        }
        $options = ['method' => $method, 'follow_location' => 0, 'ignore_errors' => true];
        if ($data !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $options['content'] = http_build_query($data);
        }
        $options['header'] = implode("\r\n", $headers);

        $body = @file_get_contents(rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/'), false,
            stream_context_create(['http' => $options]));
        $responseHeaders = $http_response_header ?? [];

        $status   = 0;
        $location = null;
        foreach ($responseHeaders as $line) {
            if (preg_match('#^HTTP/\S+ (\d{3})#', $line, $m)) {
                $status = (int) $m[1];
            } elseif (stripos($line, 'Location:') === 0) {
                $location = trim(substr($line, 9));
            } elseif (stripos($line, 'Set-Cookie:') === 0) {
                // Nur "Name=Wert" vor dem ersten Semikolon merken
                [$pair] = explode(';', trim(substr($line, 11)), 2);
                [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
                $this->cookies[$name] = $value;
            }
        }
        return ['status' => $status, 'location' => $location, 'body' => $body === false ? '' : $body];
    }
}
