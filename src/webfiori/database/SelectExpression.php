<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace webfiori\database;
/**
 * Description of Select
 *
 * @author Ibrahim
 */
class SelectExpression extends Expression {
    private $table;
    private $selectCols;
    public function __construct(Table $table) {
        parent::__construct('');
        $this->table = $table;
        $this->selectCols = [];
    }
    public function addColumn($colKey, $alias = null) {
        if ($colKey != '*') {
            $colObj = $this->getTable()->getColByKey($colKey);
            if ($colObj === null) {
                $tblName = $this->getTable()->getName();
                throw new DatabaseException("The table $tblName has no column with key '$colKey'.");
            }
            $colObj->setAlias($alias);
            $this->selectCols[] = $colObj;
        }
    }
    public function addExpression(Expression $expr) {
        $this->selectCols[] = $expr;
    }
    public function colsCount() {
        return count($this->selectCols);
    }
    public function select(array $colsOrExprs) {
        foreach ($colsOrExprs as $index => $colOrExprOrAlias) {
            if ($colOrExprOrAlias instanceof Expression) {
                $this->addExpression($colOrExprOrAlias);
            } else if (gettype($index) == 'string') {
                $this->addColumn($index, $colOrExprOrAlias);
            } else {
                $this->addColumn($colOrExprOrAlias);
            }
        }
    }
    /**
     * Clears all columns and expressions in the select.
     * 
     * @since 1.0
     */
    public function clear() {
        $this->selectCols = [];
    }
    public function getValue() {
        $colsStr = $this->getColsStr();
        return "select $colsStr from ".$this->getTable()->getName();
    }
    public function getColsStr() {
        if (count($this->selectCols) == 0) {
            $colsStr = '*';
        } else {
            $selectArr = [];
            foreach ($this->selectCols as $colObjOrExpr) {
                if ($colObjOrExpr instanceof Column) {
                    $colObjOrExpr->setWithTablePrefix(true);
                    $alias = $colObjOrExpr->getAlias();
                    if ($alias !== null) {
                        $selectArr[] = $colObjOrExpr->getName().' as '.$alias;
                    } else {
                        $selectArr[] = $colObjOrExpr->getName();
                    }
                } else {
                    $selectArr[] = $colObjOrExpr->getValue();
                }
            }
            $colsStr = implode(', ', $selectArr);
        }
        return $colsStr;
    }
    /**
     * Returns the table which is associated with the select expression.
     * 
     * @return Table The table which is associated with the select expression.
     * 
     * @since 1.0
     */
    public function getTable() {
        return $this->table;
    }
}
