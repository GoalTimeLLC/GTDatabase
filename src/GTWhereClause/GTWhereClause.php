<?php

namespace GoalTimeLLC\GTDatabase\GTWhereClause;

use Exception;
use GoalTimeLLC\GTDatabase\GTWhereClause\GTClauseValue;

class GTWhereClause
{
    const GTWHERE_EQUAL = 'EQUAL';
    const GTWHERE_AND = 'AND';
    const GTWHERE_OR = 'OR';
    const GTWHERE_GREATER_THAN = 'GREATER_THAN';
    const GTWHERE_LESS_THAN = 'LESS_THAN';
    const GTWHERE_GREATER_THAN_OR_EQUAL_TO = 'GREATER_THAN_OR_EQUAL_TO';
    const GTWHERE_LESS_THAN_OR_EQUAL_TO = 'LESS_THAN_OR_EQUAL_TO';
    const GTWHERE_BETWEEN = 'BETWEEN';
    const GTWHERE_IS_NULL = 'IS_NULL';
    const GTWHERE_IN = 'IN';
    const GTWHERE_IS_TRUE = 'IS';
    const GTWHERE_CUSTOM = 'CUSTOM';
    const GTWHERE_AND_MANY_BROKEN = 'AND';
    const GTWHERE_OR_MANY_BROKEN = 'OR';

    private GTClauseValue|GTWhereClause $oValue1;
    private GTClauseValue|GTWhereClause $oValue2;
    private string $constClauseType;
    private bool $bIsNot;
    private string $sWhereClauseFinal1;
    private string $sWhereClauseFinal2;

    /**
     * Used to construct a new where clause. GTWhereClauses can be nested.
     *
     * $oValue1 and $oValue2 can be any of the following:
     *
     *      Test
     * @param GTClauseValue|GTWhereClause $oValue1
     * Pass either a Value (using GTClauseValue)
     * @param string $constClauseType
     * @param GTClauseValue|GTWhereClause|null $oValue2
     * @param bool $bIsNot
     * @throws Exception
     */
    public function __construct(GTClauseValue|GTWhereClause $oValue1, string $constClauseType, GTClauseValue|GTWhereClause $oValue2=null, bool $bIsNot = false )
    {
        if(
            !( $oValue1 instanceof GTClauseValue ) &&
            !( $oValue1 instanceof GTWhereClause )
        ) throw new Exception('$oWhereClauseValue1 must be of type GTWhereClause of GTClauseValue.');
        if(
            !( $oValue2 instanceof GTClauseValue ) &&
            !( $oValue2 instanceof GTWhereClause )
        ) throw new Exception('$oWhereClauseValue2 must be of type GTWhereClause of GTClauseValue.');

        $this->oValue1 = $oValue1;
        $this->oValue2 = $oValue2;
        $this->constClauseType = $constClauseType;
        $this->bIsNot = $bIsNot;
    }

    /**
     * @throws Exception
     */
    public function toSql(): string {

        $sWhereClauseSql1 = '';
        $sWhereClauseSql2 = '';

        if( $this->oValue1 instanceof GTWhereClause ) {
            $sWhereClauseSql1 = $this->oValue1->toSql();
        } elseif( $this->oValue1 instanceof GTClauseValue ) {
            switch( $this->oValue1->getWhereClauseValueType() ) {
                case GTClauseValue::GTWHERECLAUSE_ITEM_COLUMN:
                case GTClauseValue::GTWHERECLAUSE_ITEM_NUMBER:
                case GTClauseValue::GTWHERECLAUSE_ITEM_EXECUTION_SQL:
                    $sWhereClauseSql1 = $this->oValue1->getValue();
                    break;
                case GTClauseValue::GTWHERECLAUSE_ITEM_TEXT:
                    $sWhereClauseSql1 = '\''.$this->oValue1->getValue().'\'';
                    break;
                case GTClauseValue::GTWHERECLAUSE_ITEM_EXECUTION:
                    eval( '$sWhereClauseSql1 = '.$this->oValue1->getValue().';' );
                    break;
                case GTClauseValue::GTWHERECLAUSE_ITEM_ARRAY:
                    throw new Exception('$oWhereClauseValue1 cannot be of ARRAY GTClauseValue type.');
                default:
                    throw new Exception('$oWhereClauseValue1 is an unknown GTClauseValue type.');
            }
        }

        if( $this->oValue2 instanceof GTWhereClause ) {
            $sWhereClauseSql2 = $this->oValue2->toSql();
        } elseif( $this->oValue2 instanceof GTClauseValue ) {
            switch( $this->oValue2->getWhereClauseValueType() ) {
                case GTClauseValue::GTWHERECLAUSE_ITEM_COLUMN:
                case GTClauseValue::GTWHERECLAUSE_ITEM_NUMBER:
                case GTClauseValue::GTWHERECLAUSE_ITEM_EXECUTION_SQL:
                case GTClauseValue::GTWHERECLAUSE_ITEM_ARRAY:
                    $sWhereClauseSql2 = $this->oValue2->getValue();
                    break;
                case GTClauseValue::GTWHERECLAUSE_ITEM_TEXT:
                    $sWhereClauseSql2 = '\''.$this->oValue2->getValue().'\'';
                    break;
                case GTClauseValue::GTWHERECLAUSE_ITEM_EXECUTION:
                    $sWhereClauseSql2 = '';
                    eval( '$sWhereClauseSql2 = '.$this->oValue2->getValue().';' );
                    break;
                default:
                    throw new Exception('$oWhereClauseValue2 is an unknown GTClauseValue type.');
            }
        }

        switch( $this->constClauseType ) {
            case static::GTWHERE_EQUAL:
                if( $this->bIsNot ) {
                    $sSqlOutput = $sWhereClauseSql1.' <> '.$sWhereClauseSql2;
                } else {
                    $sSqlOutput = $sWhereClauseSql1.' = '.$sWhereClauseSql2;
                }

                break;
            case static::GTWHERE_BETWEEN:
                if( !is_array($sWhereClauseSql2) ) {
                    throw new Exception( 'For GTWHERE_BETWEEN, $oColumnValueOrWhereClause2 must be an array.' );
                }
                if( $this->bIsNot ) {
                    $sSqlOutput = $sWhereClauseSql1 . ' NOT BETWEEN (' . $sWhereClauseSql2[0] . ') AND (' . $sWhereClauseSql2[1] . ')';
                } else {
                    $sSqlOutput = $sWhereClauseSql1 . ' BETWEEN (' . $sWhereClauseSql2[0] . ') AND (' . $sWhereClauseSql2[1] . ')';
                }
                break;
            case static::GTWHERE_GREATER_THAN:
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ' <= ' . $sWhereClauseSql2;
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ' > ' . $sWhereClauseSql2;
                }
                    break;
            case static::GTWHERE_LESS_THAN:
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ' >= ' . $sWhereClauseSql2;
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ' < ' . $sWhereClauseSql2;
                }
                    break;
            case static::GTWHERE_GREATER_THAN_OR_EQUAL_TO:
                if( $this->bIsNot ) {
                    $sSqlOutput = '('.$sWhereClauseSql1.' < '.$sWhereClauseSql2;
                } else {
                    $sSqlOutput = '('.$sWhereClauseSql1.' >= '.$sWhereClauseSql2;

                }
                    break;
            case static::GTWHERE_LESS_THAN_OR_EQUAL_TO:
                if( $this->bIsNot ) {
                    $sSqlOutput = '('.$sWhereClauseSql1.' > '.$sWhereClauseSql2;
                } else {
                    $sSqlOutput = '('.$sWhereClauseSql1.' <= '.$sWhereClauseSql2;
                }
                    break;
            case static::GTWHERE_IN:
                if( !is_array($sWhereClauseSql2) ) {
                    throw new Exception( 'For GTWHERE_IN, $oColumnValueOrWhereClause2 must be an array.' );
                }
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') NOT IN (' . implode(',', $sWhereClauseSql2) . ')';
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') IN (' . implode(',', $sWhereClauseSql2) . ')';

                }
                break;
            case static::GTWHERE_IS_NULL:
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') IS NOT NULL';
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') IS NULL';
                }
                break;
            case static::GTWHERE_IS_TRUE:
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') = TRUE';
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') = FALSE';
                }
                    break;
            case static::GTWHERE_AND:
                if( $this->bIsNot ) {
                    $sSqlOutput = 'NOT ((' . $sWhereClauseSql1 . ') AND (' . $sWhereClauseSql2 . '))';
                } else {
                    $sSqlOutput = '(' . $sWhereClauseSql1 . ') AND (' . $sWhereClauseSql2 . ')';
                }
                break;
            case static::GTWHERE_OR:
                if( $this->bIsNot ) {
                    $sSqlOutput = 'NOT (('. $sWhereClauseSql1.') OR ('.$sWhereClauseSql2.'))';
                } else {
                    $sSqlOutput = '('. $sWhereClauseSql1.') OR ('.$sWhereClauseSql2.')';
                }
                break;
            case static::GTWHERE_AND_MANY_BROKEN:
                $aAndComponents = [];
                foreach( $sWhereClauseSql2 as $oWhereClause2Item ) {
                    if( !($oWhereClause2Item instanceof GTWhereClause) ) {
                        throw new Exception('For GTWHERE_AND, oColumnNameOrValueOrWhereClause2 must be an array of GTWhereClauses.');
                    }
                    $aAndComponents[] = $oWhereClause2Item->toSql();
                }
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . implode(' AND ', $aAndComponents) . ')';
                } else {
                    $sSqlOutput = 'NOT (' . implode(' AND ', $aAndComponents) . ')';
                }
                break;
            case static::GTWHERE_OR_MANY_BROKEN:
                $aOrComponents = [];
                foreach( $sWhereClauseSql2 as $oWhereClause2Item ) {
                    if( !($oWhereClause2Item instanceof GTWhereClause) ) {
                        throw new Exception('For GTWHERE_AND, oColumnNameOrValueOrWhereClause2 must be an array of GTWhereClauses.');
                    }
                    $aOrComponents[] = $oWhereClause2Item->toSql();
                }
                if( $this->bIsNot ) {
                    $sSqlOutput = '(' . implode(' OR ', $aOrComponents) . ')';
                } else {
                    $sSqlOutput = 'NOT (' . implode(' OR ', $aOrComponents) . ')';
                }
                break;
            case static::GTWHERE_CUSTOM:
                $sSqlOutput = $sWhereClauseSql1;
                echo "\n".'SOMETHING WEIRD IS HAPPENING. '.__FILE__.', line '.__LINE__;
                break;
            default:
                throw new Exception('Must set $constWhereClauseType to one of the GTWhereClause:: constants' );
        }
        $this->sWhereClauseFinal1 = $sWhereClauseSql1;
        $this->sWhereClauseFinal2 = $sWhereClauseSql2;
        return $sSqlOutput;
    }

    /**
     * @throws Exception
     */
    public function toJson(): string {
        $this->toSql();
        $aThis = [
            'oColumnNameOrWhereClause1' => $this->sWhereClauseFinal1,
            'constWhereClauseType' => $this->constClauseType,
            'oColumnNameOrValueOrWhereClause2' => $this->sWhereClauseFinal2,
            'bIsNot' => $this->bIsNot
        ];
        return json_encode( $aThis );
    }
}