<?php

interface Database
{
    public function executeQuery($sql, $params = []);

    public function lastInsertId();

    public function fetchAll($sql, $params = []);

    public function fetchOne($sql, $params = []);
}