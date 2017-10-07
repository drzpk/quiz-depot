<?php

namespace App\Library;

/**
 * Klasa definiuje różne stałe dla programu.
 */
class Constants {

    /**
     * Zwraca maksymalne rozmiary pól dla danej tabeli i kolumny.
     * W przypadku braku dopasowania zwracana jest wartość 0.
     */
    public static function getDbConstraints($table, $column) {
        $array = [
            'categories' => [
                'name' => 45,
                'description' => 45,
                'image' => 256
            ],
            'tags' => [
                'name' => 32
            ],
            'quizzes' => [
                'name' => 32,
            ],
            'questions' => [
                'question' => 128,
                'image' => 256,
                'right_answer' => 128,
                'wrong_answer_1' => 128,
                'wrong_answer_2' => 128,
                'wrong_answer_3' => 128,
                'tags' => 256
            ]
        ];

        $value = @$array[$table][$column];
        if ($value !== null)
            return $value;
        else
            return 0;
    }
}