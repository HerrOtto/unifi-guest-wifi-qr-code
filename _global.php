<?php

/**
 * Generates a random word with a specified minimum and maximum length.
 *
 * @param int $minLength The minimum length of the generated word.
 * @param int $maxLength The maximum length of the generated word.
 * @return string The generated random word.
 */
function generateRandomWord($minLength, $maxLength) {
    $consonants = 'bcdfghjkmnpqrstvwxyz'; // l
    $vowels = 'aeu'; // io
    $length = mt_rand($minLength, $maxLength); // Generate a random length between min and max
    $word = '';

    for ($i = 0; $i < $length; $i++) {
        $isEven = ($i % 2 === 0); // Determine if the current position is even
        $source = $isEven ? $consonants : $vowels; // Use consonants for even positions and vowels for odd positions
        $randomIndex = mt_rand(0, strlen($source) - 1); // Generate a random index within the source string
        $word .= $source[$randomIndex]; // Append a randomly selected character to the word
    }

    return ucfirst($word); // Capitalize the first letter of the generated word
}
