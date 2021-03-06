<?php

require_once __DIR__ . '/SymfonyRequirements.php';

use Symfony\Component\Process\ProcessBuilder;

/**
 * This class specifies all requirements and optional recommendations that are necessary to run the Oro Application.
 */
class OroRequirements extends SymfonyRequirements
{
    const REQUIRED_PHP_VERSION = '5.4.4';

    public function __construct()
    {
        parent::__construct();

        $jreExists = new ProcessBuilder(array('java', '-version'));
        $jreExists = $jreExists->getProcess();

        $jreExists->run();

        $phpVersion  = phpversion();
        $gdVersion   = defined('GD_VERSION') ? (float) GD_VERSION : null;
        $curlVersion = function_exists('curl_version') ? curl_version() : null;
        $jreExists   = strpos($jreExists->getErrorOutput(), 'java version') !== false;

        $this->addOroRequirement(
            version_compare($phpVersion, self::REQUIRED_PHP_VERSION, '>='),
            sprintf('PHP version must be at least %s (%s installed)', self::REQUIRED_PHP_VERSION, $phpVersion),
            sprintf('You are running PHP version "<strong>%s</strong>", but Oro needs at least PHP "<strong>%s</strong>" to run.
                Before using Oro, upgrade your PHP installation, preferably to the latest version.',
                $phpVersion, self::REQUIRED_PHP_VERSION),
            sprintf('Install PHP %s or newer (installed version is %s)', self::REQUIRED_PHP_VERSION, $phpVersion)
        );

        $this->addOroRequirement(
            null !== $gdVersion && $gdVersion >= 2.0,
            'GD extension must be at least 2.0',
            'Install and enable the <strong>JSON</strong> extension.'
        );

        $this->addOroRequirement(
            function_exists('mcrypt_encrypt'),
            'mcrypt_encrypt() should be available',
            'Install and enable the <strong>Mcrypt</strong> extension.'
        );

        $this->addRecommendation(
            class_exists('SoapClient'),
            'SOAP extension should be installed (API calls)',
            'Install and enable the <strong>SOAP</strong> extension.'
        );

        $this->addRecommendation(
            null !== $curlVersion && (float) $curlVersion['version'] >= 7.0,
            'cURL extension must be at least 7.0',
            'Install and enable the <strong>cURL</strong> extension.'
        );

        // Windows specific checks
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            $this->addRecommendation(
                function_exists('finfo_open'),
                'finfo_open() should be available',
                'Install and enable the <strong>Fileinfo</strong> extension.'
            );

            $this->addRecommendation(
                class_exists('COM'),
                'COM extension should be installed',
                'Install and enable the <strong>COM</strong> extension.'
            );
        }

        $baseDir = realpath(__DIR__ . '/..');
        $mem     = $this->getBytes(ini_get('memory_limit'));

        $this->addPhpIniRequirement(
            'memory_limit',
            function ($cfgValue) use ($mem) {
                return $mem >= 256 * 1024 * 1024 || -1 == $mem;
            },
            false,
            'memory_limit should be at least 256M',
            'Set the "<strong>memory_limit</strong>" setting in php.ini<a href="#phpini">*</a> to at least "256M".'
        );

        $this->addOroRequirement(
            $jreExists,
            'Java Runtime Environment must be installed',
            'Install the <strong>JRE</strong>.'
        );

        $this->addOroRequirement(
            is_writable($baseDir . '/web/uploads'),
            'web/uploads/ directory must be writable',
            'Change the permissions of the "<strong>web/uploads/</strong>" directory so that the web server can write into it.'
        );

        $this->addOroRequirement(
            is_writable($baseDir . '/web/bundles'),
            'web/bundles/ directory must be writable',
            'Change the permissions of the "<strong>web/bundles/</strong>" directory so that the web server can write into it.'
        );
    }

    /**
     * Adds an Oro specific requirement.
     *
     * @param Boolean     $fulfilled   Whether the requirement is fulfilled
     * @param string      $testMessage The message for testing the requirement
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     */
    public function addOroRequirement($fulfilled, $testMessage, $helpHtml, $helpText = null)
    {
        $this->add(new OroRequirement($fulfilled, $testMessage, $helpHtml, $helpText, false));
    }

    /**
     * Get the list of mandatory requirements (all requirements excluding PhpIniRequirement)
     *
     * @return array
     */
    public function getMandatoryRequirements()
    {
        return array_filter($this->getRequirements(), function ($requirement) {
            return !($requirement instanceof PhpIniRequirement) && !($requirement instanceof OroRequirement);
        });
    }

    /**
     * Get the list of PHP ini requirements
     *
     * @return array
     */
    public function getPhpIniRequirements()
    {
        return array_filter($this->getRequirements(), function ($requirement) {
            return $requirement instanceof PhpIniRequirement;
        });
    }

    /**
     * Get the list of Oro specific requirements
     *
     * @return array
     */
    public function getOroRequirements()
    {
        return array_filter($this->getRequirements(), function ($requirement) {
            return $requirement instanceof OroRequirement;
        });
    }

    /**
     * @param  string $val
     * @return int
     */
    protected function getBytes($val)
    {
        if (empty($val)) {
            return 0;
        }

        preg_match('/([\-0-9]+)[\s]*([a-z]*)$/i', trim($val), $matches);

        if (isset($matches[1])) {
            $val = (int) $matches[1];
        }

        switch (strtolower($matches[2])) {
            case 'g':
            case 'gb':
                $val *= 1024;
                // no break
            case 'm':
            case 'mb':
                $val *= 1024;
                // no break
            case 'k':
            case 'kb':
                $val *= 1024;
                // no break
        }

        return (float) $val;
    }
}

class OroRequirement extends Requirement
{
}