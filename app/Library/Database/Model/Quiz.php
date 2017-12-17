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
    /** Liczba pytań do tego quizu w bazie */
    public $questionCount = 0;
    /** Liczba pytań pobieranych jednocześnie */
    public $questionChunkSize = 0;
    /** Wylosowane pytania (obiekty typu Question) */
    public $questions = [];
    /** Minimalna liczba poprawnych odpowiedzi do przejścia quizu (0 oznacza brak progu) */
    public $threshold = 0;
    /** Czas (w sekundach) potrzebny na rozwiązanie quizu. wartość 0 oznacza brak limitu czasowego. */
    public $time = 0;

    public function checkConstraints() {
        if (strlen($this->name) > 32)
            return 'nazwa quizu jest za długa';

        return null;
    }
}
