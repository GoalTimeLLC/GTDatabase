<?php

namespace GoalTimeLLC\GTDatabase;

use DateTimeZone;

class GTDatabaseConfig
{
    private string $dbServer;
    private string $dbName;
    private string $dbUser;
    private string $dbPass;
    private DateTimeZone $timezone;
    private string $errorReportingLevel;
    private bool $bDebugEnabled = false;
    private ?string $sDebugPath = null;

    public function __construct( $sDbServer, $sDbName, $sDbUser, $sDbPass ) {
        $this->dbServer = $sDbServer;
        $this->dbName = $sDbName;
        $this->dbUser = $sDbUser;
        $this->dbPass = $sDbPass;
        $this->timezone = new DateTimeZone('America/New_York');
        $this->errorReportingLevel = E_ERROR;
    }

    public function getDbServer(): string { return $this->dbServer; }
    public function getDbName(): string { return $this->dbName; }
    public function getDbUser(): string { return $this->dbUser; }
    public function getDbPass(): string { return $this->dbPass; }

    public function setTimezone( DateTimeZone $dateTimeZone ): void { $this->timezone = $dateTimeZone; }
    public function getTimezone(): DateTimeZone { return $this->timezone; }

    public function getErrorReportingLevel(): int { return $this->errorReportingLevel; }
    public function setErrorReportingLevel( int $iErrorReportingLevel ): void { $this->errorReportingLevel = $iErrorReportingLevel; }

    public function enableDebug( string $sDebugPath ): void {
        $this->bDebugEnabled = true;
        $this->sDebugPath = $sDebugPath;
    }
    public function isDebugEnabled(): bool { return $this->bDebugEnabled; }
    public function getDebugPath(): string { return $this->sDebugPath; }

}