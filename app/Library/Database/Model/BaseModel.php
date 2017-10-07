<?php

namespace App\Library\Database\Model;

use App\Library\Constants;

/**
 * Definiuje metody wspólne dla wszystkich modeli.
 */
abstract class BaseModel {
    /**
     * Sprawdza, czy wszystkie pola są poprawne. Jeśli tak, zwracana jest
     * wartość null, w przeciwnym wypadku opis błędu.
     */
    public abstract function checkConstraints();

    /**
     * Skrótowa metoda do zwracania maksymalnych rozmiarów kolumn
     * dla danej tabeli.
     */
    protected function limit($table, $column) {
        return Constants::getDbConstraints($table, $column);
    }
}