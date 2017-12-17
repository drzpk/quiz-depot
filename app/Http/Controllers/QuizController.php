<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Library\Database\DatabaseManager;

/**
 * Kontroler zarządzający rozwiązywaniem quizów.
 */
class QuizController extends Controller {

    /**
     * Generuje nowy zestaw pytań.
     */
    public function display(Request $request, DatabaseManager $manager, $quizId) {
        $quiz = $manager->getQuizById($quizId, true);
        if ($quiz == null)
            return 'Nie znaleziono quizu o podanym identyfikatorze';

        // tablica przechowująca poprawne odpowiedzi dla każdego pytania
        $solution = [];
        $answerLetters = ['a', 'b', 'c', 'd'];

        // ułożenie pytań w losowej kolejności
        foreach ($quiz->questions as $question) {
            $answers = $question->wrongAnswers;
            $answers[] = null;
            $seed = microtime();
            $this->shuffleArray($answers, $seed);

            $rightIndex = array_search(null, $answers);
            $answers[$rightIndex] = $question->rightAnswer;
            $solution[] = [
                'id' => $question->id,
                'seed' => $seed,
                'right' => $rightIndex
            ];

            $question->answers = $answers;
            $question->right = false;
            $question->wrong = false;
        }

        // zapisanie poprawnych odpowiedzi w sesji
        $request->session()->put('solution', $solution);
        $request->session()->put('quizId', $quizId);

        $params = [
            'quizId' => $quiz->id,
            'name' => $quiz->name,            
            'questions' => $quiz->questions,
            'questionChunkSize' => $quiz->questionChunkSize,
            'solution' => false
        ];
        return view('layouts.quiz', $params);
    }

    /**
     * Sprawdza poprawność odpowiedzi dla wygenerowanego wcześniej zestwau pytań.
     */
    public function solve(Request $request, DatabaseManager $manager, $quizId) {
        $session = $request->session();
        if (!$session->has('solution') || ($session->has('quizId') && $session->get('quizId') != $quizId)) {
            // Dla tej sesji nie zostały wygenerowane żadne parametry
            $session->forget('quizId');
            return redirect('/');
        }

        // Pobranie informacji o quizie
        $quiz = $manager->getQuizById($quizId, false);
        if ($quiz == null)
            return 'Nie znaleziono quizu o podanym identyfikatorze';

        $solution = $session->get('solution');
        $questionList = array_map(function ($item) {
            return $item['id'];
        }, $solution);
        $questions = $manager->getQuestionsByIds($questionList);

        $questionIndex = 1;
        $correct = 0;
        $viewQuestions = [];
        $answerLetters = ['a', 'b', 'c', 'd'];

        foreach ($solution as $entry) {
            // Zaznaczona odpowiedź
            $qname = 'q_' . $questionIndex;
            if ($request->has($qname))
                $selected = $request->$qname;
            else
                $selected = null;
            
            $question = $questions[$entry['id']];
            $seed = $entry['seed'];

            // Wygenerowanie odpowiedzi
            $answers = $question->wrongAnswers;
            $answers[] = $question->rightAnswer;
            $this->shuffleArray($answers, $seed);
            $question->answers = $answers;

            $rightLetter = $answerLetters[$entry['right']];
            $question->right = $rightLetter;
            if ($selected != null && $selected == $rightLetter) {
                // Poprawna odpowiedź
                $question->wrong = false;
                $correct++;
            } elseif ($selected != null) {
                // Błędna odpowiedź
                $question->wrong = $selected;
            } else {
                // Nie wskazano odpowiedzi
                $question->wrong = -1;
            }

            $viewQuestions[] = $question;
            $questionIndex++;   
        }

        // Obliczenie wyniku
        $score = round($correct / $quiz->questionChunkSize * 1000) / 10;
        $score = "$correct/{$quiz->questionChunkSize} ($score %)";

        if ($quiz->threshold > 0)
            $passed = $correct >= $quiz->threshold;
        else
            $passed = null;

        $params = [
            'name' => $quiz->name,
            'questions' => $viewQuestions,
            'questionChunkSize' => $quiz->questionChunkSize,
            'score' => $score,
            'passed' => $passed,
            'solution' => true
        ];
        return view('layouts.quiz', $params);
    }

    /**
     * Miesza kolejność elementów tablicy według podanego ziarna. Podanie takiego
     * samego ziarna zawsze miesza tablicę w taki sam sposób.
     * 
     * @param array tablica do pomieszania
     * @param seed  ziarno - wynik funkcji microtime()
     */
    private function shuffleArray(array &$array, string $seed) {
        $number = preg_split('/\s/', $seed)[0];
        $number = intval($number * 1000000);
        srand($number);
        for ($i = 0; $i < count($array); $i++) {
            $j = rand(0, count($array) - 1);
            $tmp = $array[$i];
            $array[$i] = $array[$j];
            $array[$j] = $tmp;
        }
    }
}
