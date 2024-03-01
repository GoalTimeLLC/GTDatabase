<?php

namespace GoalTimeLLC\GTDatabase\WhereClause;

class GTClauseValue
{
    const GTWHERECLAUSE_ITEM_COLUMN = 'COLUMN';
    const GTWHERECLAUSE_ITEM_TEXT = 'TEXT';
    const GTWHERECLAUSE_ITEM_NUMBER = 'NUMBER';
    const GTWHERECLAUSE_ITEM_EXECUTION = 'EXECUTION';
    const GTWHERECLAUSE_ITEM_EXECUTION_SQL = 'EXECUTION_SQL';
    const GTWHERECLAUSE_ITEM_ARRAY = 'ARRAY';

    private string $constWhereClauseValueType;
    private mixed $sValue;

    /**
     * Class used to place values, columns, (php code executions), and (sql executions)
     * into GTWhereClause instances.
     *
     * Preferred methods are to use the static functions to return an instance:
     *
     *      GTClauseValue::text( $val )
     *      GTClauseValue::number( $val )
     *      GTClauseValue::array( $val )
     *      GTClauseValue::column( $val )
     *      GTClauseValue::execution( $val )
     *      GTClauseValue::executionSql( $val )
     *
     * @param mixed $mValue
     * @param string $constValueType
     */
    public function __construct(mixed $mValue, string $constValueType = GTClauseValue::GTWHERECLAUSE_ITEM_COLUMN )
    {
        $this->constWhereClauseValueType = $constValueType;
        $this->sValue = $mValue;
    }

    /**
     * Return a GTClauseValue TEXT value for a GTWhereClause
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function text(mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_TEXT );
    }

    /**
     * Return a GTClauseValue NUMBER value for a GTWhereClause
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function number( mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_NUMBER );
    }

    /**
     * Return a GTClauseValue ARRAY value for a GTWhereClause ("IN" function)
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function array( mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_ARRAY );
    }

    /**
     * Return a GTClauseValue COLUMN to be used in a GTWhereClause
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function column( mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_COLUMN );
    }

    /**
     * Return a GTClauseValue PHP EXECUTION value for a GTWhereClause
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function execution( mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_EXECUTION );
    }

    /**
     * Return a GTClauseValue SQL EXECUTION value for a GTWhereClause
     * @param mixed $mValue
     * @return GTClauseValue
     */
    public static function executionSql( mixed $mValue ): GTClauseValue
    {
        return new GTClauseValue( $mValue, self::GTWHERECLAUSE_ITEM_EXECUTION_SQL );
    }

    public function isText(): bool
    {
        return ($this->constWhereClauseValueType == self::GTWHERECLAUSE_ITEM_TEXT );
    }

    public function isNumber(): bool
    {
        return ($this->constWhereClauseValueType == self::GTWHERECLAUSE_ITEM_NUMBER );
    }

    public function isColumn(): bool
    {
        return ($this->constWhereClauseValueType == self::GTWHERECLAUSE_ITEM_COLUMN );
    }

    public function isExecution(): bool
    {
        return ($this->constWhereClauseValueType == self::GTWHERECLAUSE_ITEM_EXECUTION );
    }

    public function isExecutionSql(): bool
    {
        return ($this->constWhereClauseValueType == self::GTWHERECLAUSE_ITEM_EXECUTION_SQL );
    }

    public function getValue() {
        return $this->sValue;
    }

    public function getWhereClauseValueType(): string
    {
        return $this->constWhereClauseValueType;
    }

    public function toJson(): string {
        $aThis = [
            'CLASS' => __CLASS__,
            'constDataType' => $this->constWhereClauseValueType,
            'sValue' => $this->sValue
        ];
        return json_encode( $aThis );
    }
}
