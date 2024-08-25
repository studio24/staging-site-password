<?php
declare(strict_types=1);

namespace Studio24\StagingSitePassword;

class Environment
{
    private ?string $platform = null;

    private array $platforms = [
        'wordpress' => [
            'environmentVariable' => 'WP_ENVIRONMENT_TYPE',
            'ignore' => ['production', 'local']
        ],
        'craftcms' => [
            'environmentVariable' => 'CRAFT_ENVIRONMENT',
            'ignore' => ['production', 'dev']
        ],
        'laravel' => [
            'environmentVariable' => 'APP_ENV',
            'ignore' => ['production', 'local']
        ],
        'symfony' => [
            'environmentVariable' => 'APP_ENV',
            'ignore' => ['prod', 'dev']
        ]
    ];

    private array $ignoreHostnames = [];

    public function registerEnvironment (string $environmentVariable, array $ignore)
    {
        $this->platforms['default'] = [
            'environmentVariable' => $environmentVariable,
            'ignore' => $ignore
        ];
    }

    public function setPlatform (string $platform)
    {
        if (array_key_exists($platform, $this->platforms)) {
            $this->platform = $platform;
        }
    }

    public function getPlatform (): ?array
    {
        if (null === $this->platform && defined('STAGING_SITE_PLATFORM')) {
            $this->setPlatform(STAGING_SITE_PLATFORM);
        }
        if (null !== $this->platform) {
            return $this->platforms[$this->platform];
        }
        if (isset($this->platforms['default'])) {
            return $this->platforms['default'];
        }
        return null;
    }

    public function registerHostname (string $ignoreHostname)
    {
        $this->ignoreHostnames[] = $ignoreHostname;
    }

    public function registerHostnames (array $ignoreHostnames)
    {
        $this->ignoreHostnames = array_merge($this->ignoreHostnames, $ignoreHostnames);
    }

    public function getHostname(): string
    {
        return  $_SERVER["SERVER_NAME"];
    }

    /**
     * Try to work out whether the staging environment is active, based on environment variable or hostname
     *
     * @param string $environment
     * @return bool
     * @throws Exception
     */
    public function isStaging(string $environment): bool
    {
        $platform = $this->getPlatform();
        if (null !== $platform) {
            $environmentVariable = $platform['environmentVariable'];
            $ignore = $platform['ignore'];

            // try constant
            if (defined($environmentVariable)) {
                return !in_array(constant($environmentVariable), $ignore);
            }

            // try getenv
            $env = getenv($environmentVariable);
            if ($env !== false) {
                return !in_array($env, $ignore);
            }
        }

        // try hostname
        if (!empty($this->ignoreHostnames)) {
            return !in_array($this->getHostname(), $this->ignoreHostnames);
        }

        throw new Exception('Cannot determine environment');
    }


}