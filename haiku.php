<?php
/*

This comes from www.phonicsontheweb.com -------------------

Counting Syllables

To find the number of syllables in a word, use the following steps:

1. Count the vowels in the word.
2. Subtract any silent vowels, (like the silent e at the end of a word, or the second vowel when two vowels are together in a syllable)
3. Subtract one vowel from every diphthong (diphthongs only count as one vowel sound.)
4. The number of vowels sounds left is the same as the number of syllables.

----------------
*/


class Haiku {

    var $debug = false;

    var $defaultHaiku = "Something bad happened\nDisappointment is pending\nNo haiku for you\n";
    
    var $file = null;
    var $dictionary = array();
    var $inputWords = null;
    var $vowels = "/[aeiouy]/";
    var $dipthongs = "/ai|ea|uy|oy|oo|oi|au|au|ie|io|ay|ey|ou|ei|ee/";
    var $trythongs = "/aye|eye|you/";
    var $validE = array("l");
    var $lines = array(5,7,5);

    function __construct($file) {

        $this->file = $file;

        $this->getInput();

        if (!empty($this->inputWords)) {
            $this->buildDictionary();
        }
    }

    function getInput() {
        $input = file_get_contents($this->file);
        $words = preg_split("/[\s,\.]+/", $input);

        // sanitize
        foreach($words as $word) {
            if (ctype_alpha($word)) {
                $this->inputWords[] = strtolower($word);
            }
        }
    }

    function buildDictionary() {
        
        foreach($this->inputWords as $word) {

            $sylCount = $this->getSyllableCount($word);

            if ($sylCount > 0) {

                if (!array_key_exists($sylCount, $this->dictionary)) {
                    $this->dictionary[$sylCount] = array();
                }

                if (!in_array($word, $this->dictionary[$sylCount])) {
                    $this->dictionary[$sylCount][] = $word;
                }
            }
        }
    }

    function getSyllableCount($word) {

        $chars = strlen($word);
        $lastChar = $chars - 1;

        // count all vowels
        preg_match_all($this->vowels,$word, $matches, PREG_OFFSET_CAPTURE);
   
        $syls = count($matches[0]);
   
        if ($this->debug) {
            echo "(" . $syls . " ";
        }

        // count silent 'e', but not valid 'e' endings
        if ( ($word[$lastChar] == 'e') & (!in_array($word[$lastChar-1],$this->validE)) ) {
            $syls--;
        }

        if ($this->debug) {
            echo $vowelCount . " ";
        }

        preg_match_all($this->dipthongs,$word, $matches, PREG_OFFSET_CAPTURE);
        $dips = count($matches[0]);

        $syls = $syls - $dips; 
        if ($this->debug) {
            echo $syls . " ";
        }

        preg_match_all($this->trythongs,$word, $matches, PREG_OFFSET_CAPTURE);
        $trys = count($matches[0]);

        $syls = $syls - $trys; 

        if ($this->debug) {
            echo $syls . ") ".$word. "\n";;
        }

        if ($syls < 1) {
            $syls = 1;
        }
    
        return $syls;
    }

    function getHaiku() {

        if (empty($this->dictionary)) {
            return $this->defaultHaiku;
        }

        $haiku = "";

        foreach ($this->lines as $max) {
            
            while ($max != 0 ) {

                $syllables = mt_rand(1,$max);

                if ($this->debug) {
                    echo "\n". $max . " " . $syllables ." ";
                }
               
                if (array_key_exists($syllables,$this->dictionary)) {

                    $key = array_rand($this->dictionary[$syllables]); 

                    $word = $this->dictionary[$syllables][$key]; 

                    if ($this->debug) {
                        echo $word ."\n";
                    }

                    $haiku .= $word; 
                    $haiku .= " ";

                    $max = $max - $syllables;
                }

            }
            $haiku .= "\n";
        }

        return $haiku;

    }
}

$file = "test.txt";
$h = new Haiku($file);
$haiku = $h->getHaiku();
echo $haiku;
