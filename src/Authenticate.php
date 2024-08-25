<?php
declare(strict_types=1);

namespace Studio24\StagingSitePassword;

class Authenticate
{
    private bool $error = false;

    /**
     * @var int Cookie lifetime in seconds, defaults to 1 day
     */
    private int $cookieLifetime = 86400;

    private string $cookieName = 'staging_site_remember_login';

    private ?string $passwordHash = null;

    public function setPasswordHash(string $passwordHash)
    {
        $this->passwordHash = $passwordHash;
    }

    protected function getPasswordHash(): ?string
    {
        // Try to get password from env variable or PHP constant
        if (empty($this->passwordHash)) {
            $password = getenv('STAGING_SITE_PASSWORD', true);
            if ($password !== false) {
                $this->passwordHash = $password;
                return $this->passwordHash;
            }
            if (defined('STAGING_SITE_PASSWORD')) {
                $this->passwordHash = STAGING_SITE_PASSWORD;
            }
            if (empty($this->passwordHash)) {
                throw new Exception('Staging site password not set');
            }
        }
        return $this->passwordHash;
    }

    public function setCookieName(string $cookieName)
    {
        $this->cookieName = $cookieName;
    }

    public function setCookieLifetime(int $seconds)
    {
        $this->cookieLifetime = $seconds;
    }

    public function setCookieLifetimeInDays(int $days)
    {
        $this->cookieLifetime = $days * 86400;
    }

    /**
     * Check authentication for the staging site
     * @return bool
     * @throws Exception
     */
    public function authenticate(): bool
    {
        // Login attempt
        if (isset($_POST['staging_site_login']) && isset($_POST['password'])) {
            if ($this->checkPassword($_POST['password'])) {
                $this->storeLoginInCookie();
                return true;
            } else {
                $this->error = true;
            }
        }

        // Logout attempt
        if (isset($_GET['staging_site_logout'])) {
            $this->clearLoginCookie();
            return false;
        }

        // Check if user is already logged in
        if ($this->checkLoginCookie()) {
            // Update cookie (don't do this on every request)
            if (mt_rand(1, 10) > 7) {
                $this->storeLoginInCookie();
            }
            return true;
        }

        // User is not logged in
        $this->clearLoginCookie();
        return false;
    }

    protected function getUniqueStringForUser(): string
    {
        $identifier = '';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $identifier .= $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $identifier .= $_SERVER['REMOTE_ADDR'];
        }
        $identifier .= ':' . $_SERVER['HTTP_USER_AGENT'] . ':' . md5($this->getPasswordHash());
        return $identifier;
    }

    /**
     * Store a unique value in the cookie based on the user agent and hashed password
     * @return void
     */
    protected function storeLoginInCookie()
    {
        $hash = password_hash($this->getUniqueStringForUser(), PASSWORD_DEFAULT);
        setcookie($this->cookieName, $hash, time() + $this->cookieLifetime, '/', '', true, true);
    }

    protected function clearLoginCookie()
    {
        setcookie($this->cookieName, '', time() - 3600, '/', '', true, true);
    }

    protected function checkLoginCookie(): bool
    {
        if (!isset($_COOKIE[$this->cookieName])) {
            return false;
        }
        $cookieHash = $_COOKIE[$this->cookieName];
        return password_verify($this->getUniqueStringForUser(), $cookieHash);
    }

    protected function clearLoginSession()
    {
        unset($_SESSION[$this->cookieName]);
    }

    protected function checkPassword(string $password): bool
    {
        if (empty($this->getPasswordHash())) {
            throw new Exception('Staging site password not set');
        }
        return password_verify($password, $this->getPasswordHash());
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function hasError(): bool
    {
        return $this->error;
    }
}