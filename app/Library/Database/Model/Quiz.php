<?php

namespace App\Library\Database\Model;

class Quiz {
    /** Identyfikator quizu */
    public $id = 0;
    /** Kategoria, do której należy quiz */
    public $category = null;
    /** Nazwa quizu */
    public $name = "";
    /** Data utworzenia quizu, format: "<data> <godzina>" */
    public $created = 0;
    /** Liczba rozwiązań quizu */
    public $attempts = 0;
    /** Liczba pytań pobieranych jednocześnie */
    public $questionAmount = 0;
    /** Pytania (obiekty typu Question) */
    public $questions = [];

    public function checkConstraints() {
        if (strlen($this->name) > 32)
            return 'nazwa quizu jest za długa';

        return null;
    }
}