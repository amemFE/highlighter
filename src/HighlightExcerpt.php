<?php

namespace writecrow\Highlighter;

/**
 * Class HighlightExcerpt.
 *
 * Highlight words/phrases per specifications.
 *
 * @author markfullmer <mfullmer@gmail.com>
 *
 * @link https://github.com/writecrow/highlighter/
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class HighlightExcerpt {

  private static $regex = [
    'alpha_only' => [
      'start' => '/[^a-zA-Z>]',
      'end' => '[^a-zA-Z<]/',
    ],
    'non_alpha_only' => [
      'start' => '/(.)',
      'end' => '(.)/',
    ],
  ];

  /**
   * Given a word, return its lemma form.
   *
   * @param string $text
   *    The string to highlight.
   * @param string[] $tokens
   *    The words/phrases to be highlighted.
   * @param string $method
   *    Either "word" or "lemma".
   * @param int $length
   *    The approximate target length of the entire excerpt.
   *
   * @return string
   *    The highlighted text.
   */
  public static function highlight($text, array $tokens, $method = 'word', $length = '300') {
    $excerpt_list = [];
    $text = strip_tags($text);
    // We pad this so that matches at the beginning & end of text are honoured.
    $text = ' ' . $text . ' ';
    if (empty($tokens)) {
      return substr($text, 0, $length);
    }
    foreach ($tokens as $key => $value) {
      if (empty($value)) {
        unset($tokens[$key]);
      }
    }
    $ideal_length = self::getIdealLength($length, count($tokens));
    // @todo: if lemma search, retrieve lemmas.
    foreach ($tokens as $token) {
      if (empty($token)) {
        continue;
      }
      $match = self::findFirstMatchPosition($text, $token);
      if ($match['pos'] >= 0) {
        // If this is more than 50 characters into the start of the text,
        // start the excerpt at 50 characters before the instance.
        $rstart = $match['rstart'];
        $rend = $match['rend'];
        $start = $match['pos'] - 50 < 0 ? 0 : $match['pos'] - 50;
        $excerpt = substr($text, $start, $ideal_length);
        $replacement = $match['f'] . '<mark>' . $match['string'] . '</mark>' . $match['l'];
        if ($match['sensitive']) {
          $excerpt = preg_replace($rstart . $match['string'] . $rend, $replacement, $excerpt);
        }
        else {
          $replacement = $match['f'] . '<mark>' . strtolower($match['string']) . '</mark>' . $match['l'];
          $excerpt = preg_replace($rstart . strtolower($match['string']) . $rend, $replacement, $excerpt);
          $replacement = $match['f'] . '<mark>' . ucfirst($match['string']) . '</mark>' . $match['l'];
          $excerpt = preg_replace($rstart . ucfirst($match['string']) . $rend, $replacement, $excerpt);
        }
        // Try to trim the excerpt to a word boundary.
        $word_boundary = substr($excerpt, strpos($excerpt, ' '), strrpos($excerpt, ' '));
        $word_boundary = substr($excerpt, strpos($excerpt, ' '), strrpos($excerpt, ' '));
        $excerpt_list[] = "..." . $excerpt . "...";
      }
    }
    return implode('<br />', $excerpt_list);
  }

  private static function findFirstMatchPosition($text, $token) {
    // Determine whether the token is quoted or not.
    $quoted = FALSE;
    $alpha = 'alpha_only';
    $falpha = 'alpha_only';
    $lalpha = 'alpha_only';
    $first = substr($token, 0, 1);
    $last = substr($token, -1);
    if ($first == '"' && $last == '"') {
      $token = trim($token, '"');
      $quoted = TRUE;
    }
    preg_match('/[^a-zA-Z]/', substr($token, 0, 1), $non_alpha);
    if (isset($non_alpha[0])) {
      $falpha = 'non_alpha_only';
    }
    preg_match('/[^a-zA-Z]/', substr($token, -1), $non_alpha);
    if (isset($non_alpha[0])) {
      $lalpha = 'non_alpha_only';
    }
    $rstart = self::$regex{$falpha}['start'];
    $rend = self::$regex{$lalpha}['end'];
    if ($quoted) {
      preg_match($rstart . $token . $rend, $text, $match);
    }
    else {
      preg_match($rstart . $token . $rend . 'i', $text, $match);
    }
    
    if (isset($match[0])) {
      $first_char = substr($match[0], 0, 1);
      $last_char = substr($match[0], -1);
      if ($quoted) {
        $pos = strpos($text, $match[0]);
      }
      else {
        $pos = stripos($text, $match[0]);
      }
      if ($pos >= 0) {
        return [
          'string' => $token,
          'f' => $first_char,
          'l' => $last_char,
          'pos' => $pos,
          'sensitive' => $quoted,
          'rstart' => $rstart,
          'rend' => $rend
        ];
      }
    }
    return ['pos' => -1];
  }

  private static function getIdealLength($length, $count) {
    switch ($count) {
      case 1:
        return (int) $length;

      case 2:
        return (int) $length / 2;

      default:
        return (int) $length / 3;
    }
  }

}