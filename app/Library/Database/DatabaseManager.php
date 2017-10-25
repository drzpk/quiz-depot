<?php

namespace App\Library\Database;

use Illuminate\Support\Facades\DB;
use App\Library\Database\Model\Category;
use App\Library\Database\Model\Quiz;
use App\Library\Database\Model\Question;

/**
 * Klasa udostępnia prosty interfejs do zarządzania bazą danych aplikacji QuizDepot.
 *
 * Jeśli wykonywanie jakiejkolwiek metody zakończy się sukcesem, zwracana jest wartość
 * konwertowalna to true ($val == true), w przeciwnym razie - false. Opis ostatniego
 * błędu można uzyskać za pomocą metody getLastErrorDesc.
 */
class DatabaseManager {

    private $lastErrorDesc;

    /**
     * Zwraca obiekt kategorii na podstawie nazwy lub null, jeśli dana kategoria
     * nie została odnaleziona.
     */
    public function getCategory($categoryName) {
        $result = DB::table('categories')->where('name', '=', $categoryName)->first();
        if ($result == null)
            return null;

        // utworzenie instancji modelu kategorii
        $category = $this->constructCategory($result);

        return $category;
    }

    /**
     * Zwraca listę dostępnych kategorii.
     */
    public function getCategories() {
        $result = DB::table('categories')->get();

        $categories = [];
        foreach ($result as $row)
            $categories[] = $this->constructCategory($row);

        return $categories;
    }

    private function constructCategory($row) {
        $category = new Category();
        $category->id = $row->category_id;
        $category->name = $row->name;
        $category->description = $row->description;
        $category->image = $row->image;
        return $category;
    }

    /**
     * Zwraca obiekt quizu na podstawie nazwy lub null, jeśli quiz nie został odnaleziony.
     */
    public function getQuiz(Category $category, $quizName) {
        $result = DB::table('quizzes')
            ->where('category_id', '=', $category->id)
            ->where('name', '=', $quizName)
            ->first();

        if ($result == null)
            return null;

        $quiz = new Quiz();
        $quiz->id = $result->quiz_id;
        $quiz->category = $category;
        $quiz->name = $result->name;
        $quiz->created = $result->created;
        $quiz->attempts = $result->attempts;
        $quiz->questionAmount = $result->questions;

        return $quiz;
    }

    /**
     * Zwraca listę wszystkich quizów należących do danej kategorii.
     */
    public function getQuizzes(Category $category) {
        $result = DB::table('quizzes')
            ->select('name')
            ->where('category_id', '=', $category->id)
            ->get();

        $return = [];
        foreach ($result as $row) {
            $return[] = $this->getQuiz($category, $row->name);
        }

        return $return;
    }

    public function getQuestions(Quiz $quiz) {

    }

    /**
     * Tworzy nową kategorię i ustawia identyfikator. Jeśli ścieżka do zdjęcia
     * jest adresem URL, zdjęcie jest ładowane z tego adresu.
     */
    public function createCategory(Category &$category, $check = true) {
        // sprawdzenie, czy kategoria już istnieje
        if ($check) {
            $created = $this->getCategory($category->name);
            if ($created) {
                $category = $created;
                return true;
            }
        }

        $check = $category->checkConstraints();
        if ($check != null) {
            $this->lastErrorDesc = $check;
            return false;
        }

        $id = DB::table('categories')->insertGetId([
            'name' => $category->name,
            'description' => $category->description,
            //'image' => $category->image TODO: implementacja ładowania zdjęcia
        ]);
        if ($id == false)
            return false;

        $category->id = $id;
        return true;
    }

    /**
     * Tworzy nowy quiz należący do danej kategorii. Z obiektu quizu jest brana nazwa
     * oraz liczba pytań pobieranych jednocześnie, jeśli jest większa od zera.
     *
     * Jeśli parametr $category jest równy null, kategoria jest brana z obiektu quizu.
     */
    public function createQuiz(Quiz &$quiz) {
        // sprawdzenie, czy quiz już istneje
        $created = $this->getQuiz($quiz->category, $quiz->name);
        if ($created) {
            $quiz = $created;
            return true;
        }

        $check = $quiz->checkConstraints();
        if ($check != null) {
            $this->lastErrorDesc = $check;
            return false;
        }
        
        $query = [
            'category_id' => $quiz->category->id,
            'name' => $quiz->name
        ];
        if ($quiz->questionAmount > 0)
            $query['questions'] = $quiz->questionAmount;

        $id = DB::table('quizzes')->insertGetId($query);
        if ($id == false)
            return false;

        $quiz->id = $id;
        return true;
    }

    /**
     * Tworzy nowe pytanie należące do danego quizu.
     */
    public function createQuestion(Question &$question) {
        // TODO: obsługa tagów

        $check = $question->checkConstraints();
        if ($check != null) {
            $this->lastError = $check;
            return false;
        }

        $insert = [
            'quiz_id' => $question->quiz->id,
            'question' => $question->question,
            'right_answer' => $question->rightAnswer,
            'wrong_answer_1' => $question->wrongAnswers[0],
            'wrong_answer_2' => $question->wrongAnswers[1],
            'wrong_answer_3' => $question->wrongAnswers[2],
        ];
        if ($question->image) {
            // zapisanie zdjęcia
            $imagePath = $this->saveImage($question->image, 'question', (string) $question->quiz->id);
            if (!$imagePath)
                return false;
            $insert['image'] = $imagePath;
        }

        $id = DB::table('questions')->insertGetId($insert);
        if ($id == false)
            return false;

        $question->id = $id;
        return true;
    }

    /**
     * Usuwa kategorię i wszystkie należące do niej quizy
     */
    public function deleteCategory(Category $category) {
        $quizzes = $this->getQuizzes($category);
        foreach ($quizzes as $quiz)
            $this->deleteQuiz($quiz);

        DB::table('categories')
            ->where('category_id', '=', $category->id)
            ->delete();

        return true;
    }

    /**
     * Usuwa dany quiz i wszystkie należące do niego pytania.
     */
    public function deleteQuiz(Quiz $quiz) {
        // usuwanie rekordów
        DB::table('questions')
            ->where('quiz_id', '=', $quiz->id)
            ->delete();

        DB::table('quizzes')
            ->where('quiz_id', '=', $quiz->id)
            ->delete();

        // usuwanie zdjęć należących do quizu
        $prefix = $quiz->id . '_';
        if (strpos($prefix, '/') !== false || strpos($prefix, '.') !== false) {
            $this->lastError = 'identyfikator quizu zawiera niedozwolone znaki';
            return false;
        }

        $dir = public_path() . '/img/quiz/question/';
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $filename) {
            if (strpos($filename, $prefix) !== 0) {
                // plik nie należy do quizu
                continue;
            }

            unlink(realpath($dir . $filename));
        }

        return true;
    }

    /**
     * Zwraca opis ostatniego błędu.
     */
    public function getLastErrorDesc() {
        return $this->lastError;
    }

    /**
     * Zapisuje zdjęcie z podanej ścieżki (path lub url)
     * do podanej ścieżki znajdującej się z katalogu public/img/quiz.
     * Do nazwy zdjęcia dodawany jest prefiks.
     */
    private function saveImage($imagePath, $targetPath, $prefix) {
        $content = file_get_contents($imagePath);
        if ($content === false) {
            $this->lastError = 'Nie udało się pobrać zdjęcia';
            return false;
        }

        if (strpos($prefix, '.') !== false || strpos($prefix, '/') !== false) {
            $this->lastError = 'prefiks zawiera niedozwolone znaki';
            return false;
        }
        if ($prefix[-1] != '_')
            $prefix .= '_';

        $extension = preg_replace('/\//', '', $imagePath);
        $extension = substr($extension, strrpos($extension, '.'));
        $name = uniqid($prefix) . $extension;
        $relativePath = '/img/quiz/' . $targetPath . '/' . $name;
        $path = public_path() . $relativePath;

        if (!file_put_contents($path, $content)) {
            $this->lastError = 'Nie udało się zapisać zdjęcia';
            return false;
        }

        return $name;
    }
}