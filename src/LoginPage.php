<?php

namespace Studio24\StagingSitePassword;

class LoginPage
{
    private array $placeholders = [
        'title' => 'Login to staging website',
        'title_prefix_on_error' => 'Error: ',
        'password_field_label' => 'Password',
        'show' => 'Show',
        'hide' => 'Hide',
        'show_password' => 'Show password',
        'hide_password' => 'Hide password',
        'submit_field_label' => 'Login',
        'error_message_title' => 'There is a problem',
        'error_message' => 'The password is incorrect',
        'footer' => '',
    ];

    private string $errorMessage = '';

    private Authenticate $auth;

    public function __construct(Authenticate $auth)
    {
        $this->auth = $auth;
    }

    public function setPlaceholder(string $placeholder, string $value)
    {
        if (!array_key_exists($placeholder, $this->placeholders)) {
            return;
        }
        $this->placeholders[$placeholder] = $value;
    }

    public function parseTemplate(string $template, array $placeholders = []): string
    {
        $path = __DIR__ . '/../templates/' . $template;
        if (!file_exists($path)) {
            throw new Exception(printf('Template file %s not found', $path));
        }
        $html = file_get_contents(__DIR__ . '/../templates/' . $template);

        foreach ($placeholders as $placeholder => $value) {
            $html = str_replace('{{ ' . $placeholder . ' }}', $value, $html);
        }

        return $html;
    }

    public function displayPageAndExit(bool $exit = true)
    {
        if ($this->auth->hasError()) {
            $this->placeholders['error_message'] = $this->parseTemplate('error.html', $this->placeholders);
        } else {
            $this->placeholders['error_message'] = '';
            $this->placeholders['title_prefix_on_error'] = '';
        }
        $this->placeholders['form_action'] = str_replace('staging_site_logout', '', $_SERVER['REQUEST_URI']);
        $html = $this->parseTemplate('login.html', $this->placeholders);

        // Set headers
        http_response_code(401);
        header('Cache-Control: no-cache, no-store');
        header('X-Robots-Tag: noindex');
        header('X-Clacks-Overhead: GNU Terry Pratchett');

        // Output page & exit
        echo $html;

        if ($exit) {
            exit(0);
        }
    }

}