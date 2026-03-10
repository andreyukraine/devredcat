<?php
class ModelToolImage extends Model {
  public function resize($filename, $width, $height) {
    if (empty($filename) || !is_file(DIR_IMAGE . $filename)) {
      return '';
    }

    // Додаткова перевірка шляху до файлу
    $realpath = realpath(DIR_IMAGE . $filename);
    if ($realpath === false || substr(str_replace('\\', '/', $realpath), 0, strlen(DIR_IMAGE)) != str_replace('\\', '/', DIR_IMAGE)) {
      return '';
    }

    $extension = pathinfo($filename, PATHINFO_EXTENSION);

    $image_old = $filename;
    $image_new = 'cache/' . utf8_substr($filename, 0, utf8_strrpos($filename, '.')) . '-' . (int)$width . 'x' . (int)$height . '.' . $extension;

    if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
      // Додаємо перевірку на читання файлу перед getimagesize()
      if (!is_readable(DIR_IMAGE . $image_old)) {
        error_log("Cannot read image file: " . DIR_IMAGE . $image_old);
        return '';
      }

      $image_info = @getimagesize(DIR_IMAGE . $image_old);
      if ($image_info === false) {
        error_log("Invalid image file: " . DIR_IMAGE . $image_old);
        return '';
      }

      list($width_orig, $height_orig, $image_type) = $image_info;

      if (!in_array($image_type, array(IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF))) {
        return DIR_IMAGE . $image_old;
      }

      $path = '';
      $directories = explode('/', dirname($image_new));

      foreach ($directories as $directory) {
        $path = $path . '/' . $directory;

        if (!is_dir(DIR_IMAGE . $path)) {
          @mkdir(DIR_IMAGE . $path, 0777, true);
        }
      }

      if ($width_orig != $width || $height_orig != $height) {
        try {
          $image = new Image(DIR_IMAGE . $image_old);
          $image->resize($width, $height);
          $image->save(DIR_IMAGE . $image_new);
        } catch (Exception $e) {
          error_log("Image processing error: " . $e->getMessage());
          return '';
        }
      } else {
        copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
      }
    }

    $image_new = str_replace(' ', '%20', $image_new);

    if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
      return $this->config->get('config_ssl') . 'image/' . $image_new;
    } else {
      return $this->config->get('config_url') . 'image/' . $image_new;
    }
  }
}
