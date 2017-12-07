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

        // tablica przechowująca poprawne odpowiedzi dla każdego pytania (litery odpowiedzi)
        $rightAnswers = [];
        $answerLetters = ['a', 'b', 'c', 'd'];

        // ułożenie pytań w losowej kolejności
        foreach ($quiz->questions as $question) {
            $answers = $question->wrongAnswers;
            $answers[] = null;
            shuffle($answers);

            $right = array_search(null, $answers);
            $answers[$right] = $question->rightAnswer;
            $rightAnswers[] = [
                'id' => $question->id,
                'answers' => $answers,
                'right' => $answerLetters[$right]
            ];

            $question->answers = $answers;
            $question->right = false;
            $question->wrong = false;
        }

        // zapisanie poprawnych odpowiedzi w sesji
        $request->session()->put('answers', $rightAnswers);
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
        if (!$session->has('answers') || ($session->has('quizId') && $session->get('quizId') != $quizId)) {
            // Dla tej sesji nie zostały wygenerowane żadne parametry
            $session->forget('quizId');
            return redirect('/');
        }

        // Pobranie informacji o quizie
        $quiz = $manager->getQuizById($quizId, false);
        if ($quiz == null)
            return 'Nie znaleziono quizu o podanym identyfikatorze';

        $answers = $session->get('answers');
        $questionsIds = array_map(function ($item) {
            return $item['id'];
        }, $answers);
        $questions = $manager->getQuestionsByIds($questionsIds);

        $questionIndex = 1;
        $correct = 0;
        $viewQuestions = [];
        foreach ($answers as $answer) {
            // Zaznaczona odpowiedź
            $qname = 'q_' . $questionIndex;
            if ($request->has($qname))
                $selected = $request->$qname;
            else
                $selected = null;
            
            $question = $questions[$answer['id']];
            $question->answers = $answer['answers'];

            $question->right = $answer['right'];        
            if ($selected != null && $selected == $answer['right']) {
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
}
