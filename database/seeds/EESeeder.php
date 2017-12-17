<?php

use Illuminate\Database\Seeder;
use App\Library\Database\DatabaseManager;
use App\Library\Database\Model\Category;
use App\Library\Database\Model\Quiz;
use App\Library\Database\Model\Question;

/**
 * Klasa pobiera pytania ze strony egzamin-informatyk.pl i zapisuje je do bazy.
 */
class EESeeder extends Seeder {

    /** Ilość uruchomień pobierania każdego quizu */
    private const RUN_COUNT = 10;

    private $manager = null;
    private $uploaded = [];

    private $sent = 0;
    private $errors = 0;
    private $doubles = 0;

    public function run(DatabaseManager $manager) {
        $this->manager = $manager;

        // usunięcie starej kopii kategorii, jeśli istnieje
        $category = $manager->getCategoryByName('Technik informatyk');
        if ($category !== null) {
            $manager->deleteCategory($category);
        }

        // utworzenie lub pobranie kategorii
        $category = new Category();
        $category->name = 'Technik informatyk';
        $category->description = 'Egzaminy E.12, E.13, E.14';

        if (!$manager->createCategory($category)) {
            echo 'Nie udało się utworzyć kategorii: ' . $manager->getLastErrorDesc() . PHP_EOL;
            return;
        }

        //E.12
        $testUrl = 'http://egzamin-informatyk.pl/e12-egzamin-zawodowy-test-online';
        $resultUrl = 'http://egzamin-informatyk.pl/e12-odpowiedzi';
        $this->downloadQuiz('E.12', $category, $testUrl, $resultUrl);

        // E.13
        $testUrl = 'http://egzamin-informatyk.pl/e13-egzamin-zawodowy-test-online';
        $resultUrl = 'http://egzamin-informatyk.pl/e13-odpowiedzi';
        $this->downloadQuiz('E.13', $category, $testUrl, $resultUrl);

        // E.14
        $testUrl = 'http://egzamin-informatyk.pl/e14-egzamin-zawodowy-test-online';
        $resultUrl = 'http://egzamin-informatyk.pl/e14-odpowiedzi';
        $this->downloadQuiz('E.14', $category, $testUrl, $resultUrl);
    }

    private function downloadQuiz($name, $category, $testUrl, $resultUrl) {
        // reset statystyk
        $this->sent = 0;
        $this->errors = 0;
        $this->doubles;
        $this->uploaded = [];

        // baner
        $baner = 'Pobieranie testu: ' . $name;
        echo str_repeat('-', strlen($baner) + 4) . PHP_EOL;
        echo "| $baner |" . PHP_EOL;
        echo str_repeat('-', strlen($baner) + 4) . PHP_EOL . PHP_EOL;

        $quiz = new Quiz();
        $quiz->category = $category;
        $quiz->name = $name;
        $quiz->threshold = 20;
        $quiz->time = 60 * 60;
        if (!$this->manager->createQuiz($quiz)) {
            echo 'Nie udało się utworzyć quizu: ' . $manager->getLastErrorDesc() . PHP_EOL;
            return;
        }

        // uruchomienie pobierania kilka razy w celu pobranie większej ilości pytań
        for ($i = 1; $i <= self::RUN_COUNT; $i++) {
            echo PHP_EOL . "Pobieranie: $i/10" . PHP_EOL;
            $this->download($quiz, $testUrl, $resultUrl);
        }

        echo PHP_EOL . '>> Pobieranie zakończone << ' . PHP_EOL;
        echo "Wysłanych pytań: $this->sent" . PHP_EOL;
        echo "Niewysłanych pytań: $this->errors" . PHP_EOL;
        echo "Powtórzonych pytań: $this->doubles" . PHP_EOL;
    }

    private function download($quiz, $testUrl, $resultUrl) {
        // wysłanie zapytania w celu pobrania ciasteczek
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $testUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        $response = curl_exec($curl);
        curl_close($curl);

        // pobranie listy ciasteczek
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $response, $matches);
        $cookies = [];
        foreach ($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
        $cookies_str = '';
        foreach ($cookies as $cname => $cvalue) {
            $cookies_str .= $cname . '=' . urlencode($cvalue) . '; ';
        }

        $headers = [
            'Content-type: application/x-www-form-urlencoded',
            'Cookie: ' . $cookies_str
        ];

        // wysłanie drugiego zapytania w celu pobrania pytań i odpowiedzi
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $resultUrl);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $content = curl_exec($curl);
        curl_close($curl);

        if ($content == null) {
            echo 'nie udało się pobrać strony';
            return false;
        }

        // wyłączenie ostrzeżeń związanych ze standardem HTML5
        libxml_use_internal_errors(true);        
        $doc = new DOMDocument();
        if (!$doc->loadHTML($content)) {
            echo 'nie udało się sparsować documentu';
            return false;
        }
        libxml_use_internal_errors(false);        

        $selector = new DOMXpath($doc);
        $container = $selector->query('//*[@id="portfolio"]/div/div[2]/div');
        if ($container->length == 0) {
            echo 'nie udało się znaleźć pytań';
            return false;
        }

        $this->parse($container->item(0), $quiz);

        return true;
    }

    private function parse($container, $quiz) {
        $index = 1;
        $current_question = null;
        $right_answer = '';
        $wrong_answer_index = 0;

        foreach ($container->childNodes as $node) {
            if (!($node instanceof DOMElement) || $node->tagName != 'div') continue;

            echo '#' . $index . '. ';

            if ($node->getAttribute('class') == 'obrazek' && $node->hasChildNodes()) {
                // pytanie posiada obrazek
                $src = $node->childNodes->item(0)->getAttribute('src');
                echo $src; 
                $current_question->image = 'http://egzamin-informatyk.pl/' . $src;
            }
            else {
                $text = mb_convert_encoding($node->textContent, 'UTF-8');
                echo $text . PHP_EOL;

                if (strpos($text, (string) $index) === 0) {
                    // wysłanie starego pytania, jeśli istnieje i nie zostało już wysłane
                    if ($current_question != null) {
                        if (!in_array($current_question->question, $this->uploaded)) {
                            if (!$this->manager->createQuestion($current_question)) {
                                echo 'Nie udało się utworzyć pytania: ' . $this->manager->getLastErrorDesc();
                                $this->errors++;
                            }
                            else {
                                $this->uploaded[] = $current_question->question;
                                $this->sent++;                            
                            }
                        }
                        else {
                            echo 'Takie samo pytanie zostało już wysłane, pomijanie...' . PHP_EOL;
                            $this->doubles++;
                        }
                    }

                    // rozpoczęcie nowego pytania
                    $current_question = new Question();
                    $current_question->quiz = $quiz;
                    $start_pos = strpos($text, ' ') + 1;
                    $current_question->question = substr($text, $start_pos);

                    $index++;
                    $wrong_answer_index = 0;
                }
                elseif (strpos($text, 'Nie udzielono odpowiedzi!') === 0) {
                    // pobranie litery poprawnej odpowiedzi
                    $trim = trim($text);
                    $right_answer = $trim[-1];
                }
                elseif (in_array($text[0], ['A', 'B', 'C', 'D'])) {
                    // pobranie odpowiedzi
                    if ($text[0] == $right_answer)
                        $current_question->rightAnswer = substr($text, 3);
                    else
                        $current_question->wrongAnswers[$wrong_answer_index++] = substr($text, 3);
                }
            }
        }
    }
}
