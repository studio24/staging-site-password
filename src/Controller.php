<?php
declare(strict_types=1);

namespace Studio24\StagingSitePassword;

/**
 * @todo set password via .env
 * @todo customise login page via placeholders
 */
class Controller
{
    public Authenticate $auth;

    public LoginPage $loginPage;

    public Environment $environment;

    public function __construct()
    {
        $this->auth = new Authenticate();
        $this->loginPage = new LoginPage($this->auth);
        $this->environment = new Environment();
    }

    public function isStaging(): bool
    {
        return $this->environment->isStaging();
    }

    /**
     * Check if request is authenticated, if not display login page to user
     * @return void
     */
    public function authenticate()
    {
        // If authenticated, return back to the application
        if ($this->auth->authenticate()) {
            return;
        }

        // Display login page
        $this->loginPage->displayPageAndExit();
    }

    /**
     * Static method to run the controller
     *
     * @param string|null $passwordHash The hashed password (use password_hash('password', PASSWORD_DEFAULT) to generate)
     * @return Controller
     */
    public static function run(?string $passwordHash = null): Controller
    {
        $controller = new self();
        if (null !== $passwordHash) {
            $controller->auth->setPasswordHash($passwordHash);
        }
        $controller->authenticate();
        return $controller;
    }

}
