<?php
/**
 * Created by PhpStorm.
 * User: Christopher
 * Date: 4/19/2017
 * Time: 12:19 PM
 */

namespace GoalTimeLLC\GTDatabase;

use DateTimeZone;
use Exception;
use mysqli;
use mysqli_stmt;

class GTDatabase {

    public const DEBUG_TYPE_PREPARE = 'PREPARE';
    public const DEBUG_TYPE_SELECT = 'SELECT';
    public const DEBUG_TYPE_INSERT = 'INSERT';
    public const DEBUG_TYPE_DELETE = 'DELETE';
    public const DEBUG_TYPE_UPDATE = 'UPDATE';
    public const DEBUG_TYPE_UNKNOWN = 'UNKNOWN';
    public const DEBUG_TYPE_OTHER = 'OTHER';

    /** @var bool[] $aConfigurationStatus  */
    private static array $aConfigurationStatus = [];
    
    /** @var GTDatabaseConfig[] $aConfigurations */
    private static array $aConfigurations = [];
    
    /** @var mysqli[] $aDatabaseConnections */
    private static array $aDatabaseConnections = [];

    private string $sThisInstanceName;
    private DateTimeZone $sThisTimeZone;
    private int $iErrorReportingLevel = -1;

    /** @var string[] $aSqlErrors */
    private array $aSqlErrors = [];

    /**
     * Use this static function to pass a GTDatabaseConfig for the default (or named) instance.
     * @param GTDatabaseConfig $aGtDatabaseConfig
     * @param string $sInstanceReference Pass a name to create multiple instances (default "initial")
     * @return void
     * @throws Exception
     */
    public static function setConfigUsingObject(GTDatabaseConfig $aGtDatabaseConfig, string $sInstanceReference = 'initial' ): void
    {
        if( isset( static::$aConfigurations[ $sInstanceReference ] ) ) {
            throw new Exception('Can only configure once per GTDatabase object.');
        }
        static::$aConfigurations[ $sInstanceReference ] = $aGtDatabaseConfig;
    }

    public static function getConfigs(): array
    {
        return array_keys( self::$aConfigurations );
    }

    public static function getConfig( $sInstanceReference = 'initial' ): GTDatabaseConfig
    {
        return self::$aConfigurations[ $sInstanceReference ];
    }


    /**
     * Recommended: Use "new GTDatabaseConfig( ... )" instead.
     * Use this static function to pass a configuration for the default (or named) instance.
     * @param $sServer
     * @param $sDatabaseName
     * @param $sUsername
     * @param $sPassword
     * @param string $sInstanceReference Pass a name to create multiple instances (default "initial")
     * @param int $iErrorReportingLevel You can supply a PHP reporting level for all database instance functions (default E_ERROR)
     * @param DateTimeZone $sTimezone You can pass a DateTimeZone which is used during any database and result functions
     * @return void
     * @throws Exception
     */
    public static function setConfigManually($sServer, $sDatabaseName, $sUsername, $sPassword, string $sInstanceReference = 'initial', int $iErrorReportingLevel = E_ERROR, DateTimeZone $sTimezone = new DateTimeZone('America/New_York') ): void
    {
        if( isset( static::$aConfigurations[ $sInstanceReference ] ) ) {
            throw new Exception('Can only configure once per GTDatabase object.');
        }
        $dbConfig = new GTDatabaseConfig($sServer, $sDatabaseName, $sUsername, $sPassword);
        $dbConfig->setErrorReportingLevel( $iErrorReportingLevel );
        $dbConfig->setTimezone( $sTimezone );
    }

    private function setErrorReportingTimezone(): array {
        if( $this->iErrorReportingLevel == -1 ) {
            $this->iErrorReportingLevel = static::$aConfigurations[ $this->getInstanceName() ]['ErrorReportingLevel'];
        }
        $sOldTimezone = date_default_timezone_get();
        date_default_timezone_set( $this->sThisTimeZone->getName() );
        return [
            'oldErrorLevel' => error_reporting( $this->iErrorReportingLevel ),
            'oldTimeZone' => $sOldTimezone
            ];
    }

    /**
     * @throws Exception
     */
    private function unsetErrorReportingTimezone($aResultFromSet ): void
    {
        if( !isset( $aResultFromSet['oldErrorLevel'] ) ) throw new Exception('Must include the prior INT error level.');
        if( !isset( $aResultFromSet['oldTimeZone'] ) ) throw new Exception('Must include the prior STRING timezone.');
        error_reporting( $aResultFromSet['oldErrorLevel'] );
        date_default_timezone_set( $aResultFromSet['oldTimeZone'] );
    }

    /**
     * Create an instance of the GTDatabase Object, supplying an instance name if using multiple.
     * Requires static setConfigUsingObject() or setConfigManually() prior to instantiation.
     * If a database connection already exists for the instance, it will re-use that connection.
     * @param string $sInstanceName Pass a name to create multiple instances (default "initial")
     * @throws Exception
     */
    public function __construct(string $sInstanceName = 'initial' ) {
        $this->sThisInstanceName = $sInstanceName;
        if( !isset( static::$aConfigurations[ $sInstanceName ] ) ) {
            throw new Exception('666You must use GTDatabase::setConfig( GTDatabaseConfig $config ) before instantiating.' );
        }
        $this->iErrorReportingLevel = static::$aConfigurations[ $sInstanceName ]->getErrorReportingLevel();
        $this->sThisTimeZone = static::$aConfigurations[ $sInstanceName ]->getTimezone();
        $aOldErrorLevelTimezone = $this->setErrorReportingTimezone();

        if( !isset( static::$aDatabaseConnections[$sInstanceName] ) ) {
            $connectCounter = 0;
            $this->aSqlErrors = [];
            while(true) {
                $connectCounter++;
                try {
                    $sDatabaseServer = static::$aConfigurations[ $sInstanceName ]->getDbServer();
                    $sDatabaseUser = static::$aConfigurations[ $sInstanceName ]->getDbUser();
                    $sDatabasePass = static::$aConfigurations[ $sInstanceName ]->getDbPass();
                    $sDatabaseName = static::$aConfigurations[ $sInstanceName ]->getDbName();
                    static::$aDatabaseConnections[$sInstanceName] = mysqli_connect($sDatabaseServer, $sDatabaseUser, $sDatabasePass, $sDatabaseName);
                } catch( Exception $e ) {
                    $this->unsetErrorReportingTimezone($aOldErrorLevelTimezone);
                    echo "\n\n";
                    print_r( $e );
                    echo "\n\n";
                    exit();
                }

                if (mysqli_connect_errno()) {
                    if (mysqli_connect_errno() === 2002 && $connectCounter<60) {
                        echo "\n".__FILE__.' ('.__LINE__.')';
                        sleep(1);
                        continue;
                    }
                    die("Failed to connect to database.  Please call the Administrator for support: " . mysqli_connect_error() . "(" . mysqli_connect_errno() . ")");
                } else {
                    break;
                }
            }
            static::$aDatabaseConnections[$this->getInstanceName()]->autocommit(true);
        }
        $this->unsetErrorReportingTimezone($aOldErrorLevelTimezone);
    }

    /**
     * Return the name of the current instance (default at construction is "initial")
     * @return string
     */
    public function getInstanceName(): string
    {
        return $this->sThisInstanceName;
    }

    // ===================================================================
    // SQL EXECUTION SUPPORT FUNCTIONS
    // ===================================================================

    // TODO: For some reason, I *think* the "&$aArrayOfValues" needs to stay as is.
    //          Change and test?
    private function getReferencedValuesArray($aArrayOfValues): array {
        $aReferencedArrayOfValues = array();
        foreach ($aArrayOfValues as $sKey => $oValue) {
            $aReferencedArrayOfValues[$sKey] = &$aArrayOfValues[$sKey];
        }
        return $aReferencedArrayOfValues;
    }

    private function prepareSqlStatement( $sSql, $aParameters, $uuidTransactionId ): ?mysqli_stmt
    {
        $stmtToPrepare = static::$aDatabaseConnections[$this->getInstanceName()]->prepare($sSql);
        if (!$stmtToPrepare) {
            $this->logSQL(__function__, static::DEBUG_TYPE_PREPARE, $sSql, $aParameters, 'function: prepare (' . $uuidTransactionId . ')');
            return null;
        }
        $aCallUserFunctionData = [$stmtToPrepare];
        $iNumParams = count($aParameters);
        $sParamFormats = str_pad('', $iNumParams, "s");
        $aCallUserFunctionData[] = $sParamFormats;
        if ($iNumParams > 0) {
            foreach ($aParameters as $sParam) {
                $aCallUserFunctionData[] = $sParam;
            }
            $aCallUserFunctionDataReferenced = $this->getReferencedValuesArray($aCallUserFunctionData);
            $callResult = call_user_func_array('mysqli_stmt_bind_param', $aCallUserFunctionDataReferenced);
            if (!$callResult) {
                $this->logSQL(__function__, static::DEBUG_TYPE_PREPARE, $sSql, $aParameters, 'function: call_user_func_array (' . $uuidTransactionId . ')');
                return null;
            }
        }
        return $stmtToPrepare;
    }
    private function executeSqlStatement( $sSql, $aParameters, $uuidTransactionId, $sDebugType ): ?mysqli_stmt
    {
        try {
            $stmtToExecute = $this->prepareSqlStatement($sSql, $aParameters, $uuidTransactionId);
        } catch( Exception $e ) {
            echo "\n\n";
            print_r( $e );
            echo "\n\n";
            exit();
        }

        if( $this->hasSqlErrors() ) {
            echo "\n".'Errors? '.print_r( $this->getLastSqlError(), true );
        }

        if( !$stmtToExecute ) {
            $this->logSql(__FUNCTION__, $sDebugType, $sSql, $aParameters, 'Error on stmt->prepareSqlStmt('.$uuidTransactionId.').' );
            $stmtToExecute->close();
            return null;
        }
        $stmtToExecute->execute();

        return $stmtToExecute;
    }

    private function getInsertId( mysqli_stmt $stmtExecuted ): int
    {
        echo "\n".$stmtExecuted->insert_id."\n";
        return $stmtExecuted->insert_id;
    }
    private function getRowsAffected( mysqli_stmt $stmtExecuted ): int
    {
        return $stmtExecuted->affected_rows;
    }
    private function getDataResults( mysqli_stmt $stmtExecuted ) : ?array
    {
        $resultMeta = $stmtExecuted->result_metadata();
        if (!$resultMeta) {
            return null;
        }
        $aResultFields = [];
        while ($field = $resultMeta->fetch_field()) {
            $fieldName = $field->name;
            $aResultFields[] = $fieldName;
        }
        $resultMeta->free_result();

        $allResults = array();
        $oResults = $stmtExecuted->get_result();
        while ($aRowData = $oResults->fetch_assoc()) {
            $newRowData = array();
            foreach ($aResultFields as $sFieldName) {
                $newRowData[$sFieldName] = $aRowData[$sFieldName];
            }
            $allResults[] = $newRowData;
        }
        return $allResults;
    }
    // ===================================================================
    // END SQL EXECUTION SUPPORT FUNCTIONS
    // ===================================================================



    // ===================================================================
    // SQL INITIATION FUNCTIONS
    // ===================================================================

    /**
     * Perform multiple INSERTs on the current database connection and return the results.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameterSets An array of the arrays of Parameters typically sent.
     * @return int[]|null Returns an array of the insert IDs of the new records.
     */
    public function execSqlInsertMulti(string $sSql, array $aParameterSets = [] ): ?array {
        $aResultingInsertIds = [];
        foreach( $aParameterSets as $aParameters ) {
            $iInsertId = $this->execSqlInsert( $sSql, $aParameters );
            $aResultingInsertIds[] = $iInsertId;
        }
        return $aResultingInsertIds;
    }

    /**
     * Perform an INSERT on the current database connection and return the insert IDs.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameters Parameters to be used for the "?" substitutions.
     * @return int|null Returns the insert ID of the new record.
     */
    public function execSqlInsert(string $sSql, array $aParameters = [] ): ?int {
        $uuidTransaction = uniqid();
        $stmtExecuted = $this->executeSqlStatement( $sSql, $aParameters, $uuidTransaction, static::DEBUG_TYPE_INSERT );
        return $this->getInsertId( $stmtExecuted );
    }

    /**
     * Perform a QUERY on the current database connection and return the results.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameters Parameters to be used for the "?" substitutions.
     * @return array|null Returns an array of results.
     */
    public function execSqlQuery(string $sSql, array $aParameters = [] ): ?array {
        $uuidTransactionId = uniqid();
        try {
            $rStmtExecuted = $this->executeSqlStatement($sSql, $aParameters, $uuidTransactionId, static::DEBUG_TYPE_OTHER);
        } catch( Exception $e ) {
            echo "\n\n";
            print_r( $e );
            echo "\n\n";
            exit();
        }

        try {
            $aDataResults = $this->getDataResults( $rStmtExecuted );
        } catch( Exception $e ) {
            echo "\n\n";
            print_r( $e );
            echo "\n\n";
            exit();
        }

        if(str_starts_with(strtoupper($sSql), 'CREATE')) {
            return [];
        }

        if( count($aDataResults) == 0 ) {
            return [];
        }

        if( !$aDataResults ) {
            echo "\n".__FILE__.' ('.__LINE__.')';
            $this->logSql(__FUNCTION__, static::DEBUG_TYPE_OTHER, $sSql, $aParameters, 'Error on getDataResults.' );
            echo "\n".__FILE__.' ('.__LINE__.')';
            return null;
        }
        return $aDataResults;
    }

    /**
     * Perform a QUERY on the current database connection and return the single result.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameters Parameters to be used for the "?" substitutions.
     * @return array|null Returns an array of values for the result.
     */
    public function execSQLQuerySingle(string $sSql, array $aParameters = []): ?array {
        $aResults = static::execSqlQuery($sSql, $aParameters);
        if ($aResults === null) return null;
        if (count($aResults) === 0) return [];
        return $aResults[0];
    }

    /**
     * Perform a DELETE on the current database connection and return the number of rows deleted.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameters Parameters to be used for the "?" substitutions.
     * @return int|null Returns the count of affected rows.
     */
    public function execSqlDelete(string $sSql, array $aParameters = [] ): ?int {
        $uuidTransaction = uniqid();
        $stmtExecuted = $this->executeSqlStatement( $sSql, $aParameters, $uuidTransaction, static::DEBUG_TYPE_DELETE );
        $iRowsAffected = $this->getRowsAffected( $stmtExecuted );
        $stmtExecuted->close();
        return $iRowsAffected;
    }

    /**
     * Perform an UPDATE on the current database connection and return the number of rows changed.
     * @param string $sSql SQL Insert statement to be executed containing "?" as needed.
     * @param array $aParameters Parameters to be used for the "?" substitutions.
     * @return int|null Returns the count of affected rows.
     */
    public function execSqlUpdate(string $sSql, array $aParameters = [] ): ?int {
        $uuidTransaction = uniqid();
        $stmtExecuted = $this->executeSqlStatement( $sSql, $aParameters, $uuidTransaction, static::DEBUG_TYPE_UPDATE );
        $iRowsAffected = $this->getRowsAffected( $stmtExecuted );
        $stmtExecuted->close();
        return $iRowsAffected;
    }
    // ===================================================================
    // END SQL EXECUTION SUPPORT FUNCTIONS
    // ===================================================================



    // ===================================================================
    // DEBUGGING AND LOGGING CODE
    // ===================================================================


    public function isDebugEnabled() {
        return static::$aConfigurations[$this->getInstanceName()]['isDebugEnabled'];
    }

    /**
     * @return void
     * @throws Exception
     */
    public function enableDebug(): void
    {
        $sThisInstanceName = $this->getInstanceName();
        $sDirectoryPath = static::$aConfigurations[ $sThisInstanceName ]['debugPath'];
        if( !file_exists( $sDirectoryPath ) ) {
            throw new Exception('The debug log directory path must already exist.');
        }
        if( !is_dir( $sDirectoryPath ) ) {
            throw new Exception('Please give the directory in which you want log files.');
        }
        if( !is_writeable( $sDirectoryPath ) ) {
            throw new Exception('Log Directory must be writeable.');
        }
        static::$aConfigurations[$sThisInstanceName]['isDebugEnabled'] = true;
    }

    public function disableDebug(): void
    {
        $sThisInstanceName = $this->getInstanceName();
        static::$aConfigurations[$sThisInstanceName]['isDebugEnabled'] = false;
    }

    private function logSql($sFunctionName, $sDebugType, $sSql, $aParameters, $sMessageOrResult): void
    {
        $sThisInstanceName = $this->getInstanceName();
        if (!static::$aConfigurations[$sThisInstanceName]['isDebugEnabled']) return;
        $sTransaction = "\n\nGTDatabase: " . $sDebugType . " Error!" .
            "\n\t" . "execSQL()" .
            "\n\tSQL: " . $sSql .
            "\n\tParameters: " . print_r($aParameters, true) .
            "\n\tMessage: " . print_r($sMessageOrResult, true) .
            "\n\tBackTrace: " . print_r( debug_backtrace(), true ) .
            "\n\tFunctionName: " . $sFunctionName .
            "\n\tEnd error message.";
        $timestamp = date('YmdHm');
        $sDebugPath = static::$aConfigurations[$sThisInstanceName]['debugPath'];
        file_put_contents($sDebugPath."/GTDatabase-log-" . $timestamp, $sTransaction, FILE_APPEND);
        $this->recordSqlErrors( $sTransaction );
    }

    private function recordSqlErrors( $sTransaction ): void
    {
        $this->aSqlErrors[] = [
            static::$aDatabaseConnections[$this->getInstanceName()]->errno,
            static::$aDatabaseConnections[$this->getInstanceName()]->error,
            static::$aDatabaseConnections[$this->getInstanceName()]->error_list,
            $sTransaction
        ];
    }
    // ===================================================================
    // END DEBUG AND LOGGING CODE
    // ===================================================================



    // ===================================================================
    // ROLLBACK AND COMMIT
    // ===================================================================
    public function rollbackInit(): void
    {
        static::$aDatabaseConnections[$this->getInstanceName()]->autocommit(false);
    }
    public function rollbackCommit(): void
    {
        static::$aDatabaseConnections[$this->getInstanceName()]->commit();
        static::$aDatabaseConnections[$this->getInstanceName()]->autocommit(true);
    }
    public function rollbackRevert(): void
    {
        static::$aDatabaseConnections[$this->getInstanceName()]->rollback();
    }
    // ===================================================================
    // END ROLLBACK AND COMMIT
    // ===================================================================



    // ===================================================================
    // SQL ERROR HANDLING
    // ===================================================================
    public function getSqlErrors(): array
    {
        return $this->aSqlErrors;
    }
    public function hasSqlErrors(): bool
    {
        if( count($this->aSqlErrors) > 0 ) {
            return true;
        }
        return false;
    }
    public function getLastSqlError(): string
    {
        if (count($this->aSqlErrors)) {
            return $this->aSqlErrors[count($this->aSqlErrors) - 1];
        }
        return "";
    }
    // ===================================================================
    // END SQL ERROR HANDLING
    // ===================================================================

}




