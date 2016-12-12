<?php
$haiku = false;
if (isset($_POST['submit'])) {

    if ($_FILES["fileToUpload"]["size"] > 5000) {

        $haiku = "Sorry, your file is too large.";
    } 

    $array = explode('.', $_FILES['fileToUpload']['name']);
    $extension = end($array);

    if ($extension != 'txt') {
        $haiku = "file must end in .txt";
    }

    if ($haiku === false) {

        $file = $_FILES["fileToUpload"]["tmp_name"];
        $h = new Haiku($file);
        $haiku = $h->getHaiku();
        //echo $haiku;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Haiku Generator. Generates a haiku from a text file">
    <meta name="author" content="David Caiati">
    <link rel="icon" href="/images/jellyfish-favicon.jpg">

    <title>Haiku Generator</title>
    <!-- bootstrap -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

    <!-- Latest compiled and minified JavaScript -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<div class="container">
  <div class="starter-template text-center">
    <h1>haiku Generator</h1>
    <p class="lead">Generate a haiku from a .txt file you upload</p>

    <form action="/art/haiku.php" method="post" enctype="multipart/form-data">
     <div class="form-group text-center">
        <label for="fileToUpload">Select a file to upload</label>
        <input class="text-center" type="file" name="fileToUpload" id="fileToUpload">
        <small id="fileHelp" class="form-text text-muted">file must be a .txt file</small>
    </div>
    <div class="form-group">
        <input type="submit" value="get haiku" name="submit">
    </div>
    </form>

    <p>
    <div class="haiku-display">
<?php 
if ($haiku !== false) {
	echo $haiku;
}
?>
  </p>
  </div>
  </div>
</div><!-- /.container -->
</body>
</html>
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

    var $defaultHaiku = "Something bad happened<br />Disappointment is pending<br />No haiku for you<br />";
    
    var $file = null;
    var $dictionary = array();
    var $inputWords = null;
    var $vowels = "/[aeiouy]/";
    var $dipthongs = "/oa|ai|ea|uy|oy|oo|oi|au|au|ie|io|ay|ey|ou|ei|ee/";
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
        if ( (($word[$lastChar] == 'e') || strpos("es",$word,$lastchar-1))  
			& (!in_array($word[$lastChar-1],$this->validE)) ) {
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
            echo $syls . ") ".$word. "<br />";;
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
                    echo "<br />". $max . " " . $syllables ." ";
                }
               
                if (array_key_exists($syllables,$this->dictionary)) {

                    $key = array_rand($this->dictionary[$syllables]); 

                    $word = $this->dictionary[$syllables][$key]; 

                    if ($this->debug) {
                        echo $word ."<br />";
                    }

                    $haiku .= $word; 
                    $haiku .= " ";

                    $max = $max - $syllables;
                }

            }
            $haiku .= "<br />";
        }

        return $haiku;

    }
}

