<?php

namespace Vanderlee\SyllableBuild;

abstract class Manager
{
    /**
     * @var int
     */
    protected $logLevel;

    public function __construct()
    {
        $this->logLevel = LOG_INFO;
    }

    /**
     * @param int $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = $logLevel;
    }

    protected function info($text)
    {
        $this->log($text, LOG_INFO);
    }

    protected function warn($text)
    {
        $this->log($text, LOG_WARNING);
    }

    protected function error($text)
    {
        $this->log($text, LOG_ERR);
    }

    protected function log($text, $logLevel)
    {
        if ($logLevel <= $this->logLevel) {
            echo "$text\n";
        }
    }

    /**
     * @param string $command
     * @param bool $returnOutput
     *
     * @throws ManagerException
     *
     * @return array|string
     *
     */
    protected function exec($command, $returnOutput = false)
    {
        $commandQuiet = str_replace(' 2>&1', '', $command).' 2>&1';

        $result = exec($commandQuiet, $output, $resultCode);

        if ($result === false) {
            throw new ManagerException(
                "PHP fails to execute external programs."
            );
        } elseif ($resultCode !== 0) {
            throw new ManagerException(sprintf(
                "Command \"%s\" fails with:\n%s",
                $command,
                implode("\n", $output)
            ));
        }

        if ($returnOutput) {
            return $output;
        }

        return $result;
    }
}