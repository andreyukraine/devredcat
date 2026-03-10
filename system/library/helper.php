<?php
class Helper {
  public static function cleanProductDescription($html)
  {
    // 1. Первым делом убираем экранирование кавычек и апострофов
    $html = str_replace(['\"', "\\'"], ['"', "'"], $html);

    // 2. Декодируем HTML-сущности
    $html = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    // 3. Удаляем все style-атрибуты (включая сложные случаи)
    $html = preg_replace_callback('/<([^>]+)>/', function ($matches) {
      $tag = $matches[1];
      // Удаляем все варианты style-атрибутов
      $tag = preg_replace([
        '/\s*style=(["\']).*?\1/i',
        '/\s*style=\\\\?["\'].*?\\\\?["\']/i'
      ], '', $tag);
      return '<' . $tag . '>';
    }, $html);

    // 4. Разрешаем только безопасные теги
    $html = strip_tags($html, '<p><br><ul><ol><li><strong><b><i><span><div>');

    // 5. Удаляем пустые теги
    $html = preg_replace('/<(\w+)[^>]*>\s*<\/\1>/', '', $html);

    // 6. Удаляем символы \r\n
    $html = str_replace(['"\r\n"', '\r', '\n'], '', $html);

    //7.видаляємо пробіли зліва і справа
    $html = trim($html);

    // 8. Видаляємо ZeroWidthSpace та інші невидимі символи
    $html = preg_replace('/\&(?!nbsp\;)\w+?\;/', '', $html); // Видаляємо всі HTML-сутності типу &ZeroWidthSpace;
    $html = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $html); // Видаляємо Unicode-символи нульової ширини

    return $html;
  }

  // Інші статичні методи
  public static function anotherMethod($param) {
    return "Hello, " . $param;
  }
}
