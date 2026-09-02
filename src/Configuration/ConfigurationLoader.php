<?php

declare(strict_types=1);

namespace Nexus\Configuration;

use Nexus\Exception\ConfigurationException;

final class ConfigurationLoader
{
    public function load(string $directory): Configuration
    {
        if (!is_dir($directory)) {
            return new Configuration();
        }

        $files = glob(rtrim($directory, '/\\') . DIRECTORY_SEPARATOR . '*.php');

        if ($files === false) {
            throw new ConfigurationException(sprintf('Unable to read configuration directory "%s".', $directory));
        }

        sort($files, SORT_STRING);
        $values = [];

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            $configuration = require $file;

            if (!is_array($configuration)) {
                throw new ConfigurationException(sprintf(
                    'Configuration file "%s" must return an array.',
                    $file,
                ));
            }

            $values[$key] = $configuration;
        }

        return new Configuration($values);
    }
}
