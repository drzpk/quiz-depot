<?php

namespace App\Library\Database\Model;

class Question extends BaseModel {
    /** Identyfikator pytania */
    public $id = 0;
    /** Quiz, do którego należy pytanie */
    public $quiz = null;
    /** Treść pytania */
    public $question = "";
    /** Poprawna odpowiedź na pytanie */
    public $rightAnswer = "";
    /** Niepoprawne odpowiedzi (dokładnie 3) */
    public $wrongAnswers = ["", "", ""];
    /** Ścieżka do zdjęcia, jeśli istnieje (fizyczna lub url) */
    public $image = null;
    /** Tagi (obiekty typu Tag) */
    public $tags = [];

    public function checkConstraints() {
        if (strlen($this->question) > $this->limit('questions', 'question'))
            return 'pytanie do quizu jest za długie';
        if (strlen($this->rightAnswer) > $this->limit('questions', 'right_answer'))
            return 'odpowiedź do quizu jest za długa';
        if ($this->image != null && strlen($this->image) > $this->limit('questions', 'image'))
            return 'ścieżka do zdjęcia quizu jest za długa';
        
        // limity dla złych odpowiedzi
        if (count($this->wrongAnswers) != 3)
            return 'niepoprawna liczba złych odpowiedzi';
        $limit = $this->limit('questions', 'wrong_answer_1');
        foreach ($this->wrongAnswers as $wrongAns) {
            if (strlen($wrongAns) > $limit)
                return 'odpowiedź do quizu jest za długa';
        }

        // limity dla tagów
        $limit = $this->limit('questions', 'tags');
        $total_len = 0;
        foreach ($this->tags as $tag) {
            $len = strlen((string) $tag->id) + 1;
            $total_len += $len;
        }
        if ($total_len > $limit)
            return 'pytanie posiada za dużo tagów';

        return null;
    }
}