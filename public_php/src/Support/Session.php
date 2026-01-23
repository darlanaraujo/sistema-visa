<?php
declare(strict_types=1);

final class Session
{
    // Padrão comum de sistemas: 30 minutos de inatividade
    public const IDLE_TIMEOUT_SECONDS = 30 * 60;

    private static bool $started = false;

    public static function start(): void
    {
        if (self::$started) return;

        if (session_status() === PHP_SESSION_NONE) {
            // Cookie de sessão padrão: expira ao fechar o navegador (lifetime = 0)
            ini_set('session.cookie_lifetime', '0');
            ini_set('session.cookie_httponly', '1');

            // Em localhost sem HTTPS, não force secure.
            // Quando estiver em HTTPS, troque para 1.
            // ini_set('session.cookie_secure', '1');

            // Lax ajuda contra CSRF básico sem quebrar navegação
            ini_set('session.cookie_samesite', 'Lax');

            // Evita fixação de sessão
            ini_set('session.use_strict_mode', '1');

            session_start();
        }

        self::$started = true;
    }

    /**
     * Deve ser chamado em toda request protegida (middleware)
     * para expirar a sessão por inatividade.
     */
    public static function enforceIdleTimeout(int $seconds = self::IDLE_TIMEOUT_SECONDS): void
    {
        self::start();

        $now = time();

        // Se já existia atividade anterior:
        if (isset($_SESSION['__last_activity'])) {
            $last = (int)$_SESSION['__last_activity'];

            if (($now - $last) > $seconds) {
                self::destroy();
                return;
            }
        }

        // Atualiza atividade
        $_SESSION['__last_activity'] = $now;
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            // nada para destruir
            self::$started = false;
            $_SESSION = [];
            return;
        }

        // Limpa dados
        $_SESSION = [];

        // Remove cookie
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }

        session_destroy();
        self::$started = false;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        self::start();
        return array_key_exists($key, $_SESSION);
    }
}