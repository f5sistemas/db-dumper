<?php

namespace Spatie\DbDumper\Databases;

use Spatie\DbDumper\DbDumper;
use Spatie\DbDumper\Exceptions\DumpFailed;
use Symfony\Component\Process\Process;
use Spatie\DbDumper\Exceptions\CannotStartDump;

class Firebird extends DbDumper
{

    /**
     * @var string
     */
    protected $fbkFile;

    /**
     * @var string
     */
    protected $gbakPath;

    /**
     * Firebird constructor.
     */
    public function __construct()
    {

    }

    /**
     * @param $fbkFile
     * @return $this
     */
    public function setFbkFile($fbkFile) {

        $this->fbkFile = $fbkFile;

        return $this;

    }

    /**
     * @param $gbakPath
     * @return $this
     */
    public function setGbakPath($gbakPath) {

        $this->gbakPath = $gbakPath;

        return $this;

    }

    /**
     * Dump the contents of the database to the given file.
     *
     * @param string $dumpFile
     *
     * @throws \Spatie\DbDumper\Exceptions\CannotStartDump
     * @throws \Spatie\DbDumper\Exceptions\DumpFailed
     */
    public function dumpToFile(string $dumpFile)
    {
        $this->guardAgainstIncompleteCredentials();

        $command = $this->getDumpCommand($dumpFile);

        $process = Process::fromShellCommandline($command, null, null, null, $this->timeout);
        $process->run();

        $this->checkIfDumpWasSuccessFul($process, $dumpFile);
    }

    /**
     * Get the command that should be performed to dump the database.
     *
     * @param string $dumpFile
     *
     * @return string
     */
    public function getDumpCommand(string $dumpFile): string
    {
        /** Prevent 7.4.
         * Gbak log file Cannot Be Overwritten
         * If you specify a log file name with the -y <log file> switch, and the file already exists,
         * then even though the firebird user owns the file, and has write permissions to it, gbak cannot overwrite it.
         * You must always specify the name of a log file that doesn’t exist.
        */
        @unlink($dumpFile);

        $command = [
            "\"{$this->gbakPath}gbak\"",
            "-user {$this->userName}",
            "-pas {$this->password}",
            "{$this->dbName}",
            "{$this->fbkFile}",
            "-y \"{$dumpFile}\"",
            "-v"
        ];

        return $this->echoToFile(implode(' ', $command), null);
    }

    protected function guardAgainstIncompleteCredentials()
    {
        foreach (['userName', 'dbName', 'host', 'fbkFile'] as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw CannotStartDump::emptyParameter($requiredProperty);
            }
        }
    }

    /**
     * @param Process $process
     * @param string $outputFile
     * @throws DumpFailed
     */
    protected function checkIfDumpWasSuccessFul(Process $process, string $outputFile)
    {
        if (! $process->isSuccessful()) {
            throw DumpFailed::processDidNotEndSuccessfully($process);
        }

        if (! file_exists($outputFile)) {
            throw DumpFailed::dumpfileWasNotCreated();
        }

        if (filesize($outputFile) === 0 || filesize($this->fbkFile) === 0) {
            throw DumpFailed::dumpfileWasEmpty();
        }
    }

}
