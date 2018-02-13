<?php

namespace app\models\forms;

use Gregwar\Image\Image;
use Intervention\Image\ImageManagerStatic as ImageManager;
use Intervention\Image\Gd\Font;
use Yii;
use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;
    public $imagePath;

    protected $imageName = [];

    const TYPE_PAGES = 'pages';
    const TYPE_NEWS = 'news';
    const TYPE_CAMP = 'camps';
    const TYPE_EMAILS = 'emails';
    const TYPE_PROFILE = 'profile';

    const SAVE_QUALITY = 100;
    const FONT_ALIAS = 'public_html/fonts/sourcesanspro/sourcesanspro.ttf';
    const FONT_BORDER_COLOR = '50,50,50,0.5';
    const FONT_COLOR = '255,255,255,0.85';
    const MARK_TEXT = 'Camp-Centr.ru';

    const UPLOAD_DIR = 'public_html/photos';
    const VIEW_DIR = 'photos';

    const SCENARIO_CAMP = 'camp';

    public function rules()
    {
        return [
            [['imageFile'], 'image', 'skipOnEmpty' => false, 'mimeTypes' => 'image/*',
                'minWidth' => 50, 'minHeight' => 50, 'maxFiles' => 10, 'on' => self::SCENARIO_DEFAULT,
                'underWidth' => 'Ширина загружаемого фото "{file}" должна быть не менее {limit} пикселей',
                'underHeight' => 'Высота загружаемого фото "{file}" должна быть не менее {limit} пикселей'],

            [['imageFile'], 'image', 'skipOnEmpty' => false, 'mimeTypes' => 'image/*',
                'minWidth' => 600, 'minHeight' => 480, 'maxFiles' => 10, 'on' => self::SCENARIO_CAMP,
                'underWidth' => 'Ширина загружаемого фото "{file}" должна быть не менее {limit} пикселей',
                'underHeight' => 'Высота загружаемого фото "{file}" должна быть не менее {limit} пикселей'],
        ];
    }

    public function getImageName() {
        return count($this->imageName) == 1 ? $this->imageName[0] : $this->imageName;
    }
    
    public static function remove($src, $type = self::TYPE_CAMP) {
        $suffixes = ['', '_lg', '_md', '_sm', '_xs', '_xx'];
        
        $path = Yii::getAlias('@app/public_html');
        // /photos/pages/page_123_xs.jpg => page_123.jpg
        $name = str_replace($suffixes, '', $src);
        $name = str_replace([self::VIEW_DIR, $type], '', $name);
        $name = trim($name, '/');
        
        foreach ($suffixes AS $suffix) {
            $file_path = $path . self::getSrc($name, $type, $suffix);
            if (file_exists($file_path)) unlink($file_path);
        }
    }
    
    public function upload($type = self::TYPE_CAMP)
    {
        if ($this->validate()) {
            /** @var $files UploadedFile[] */
            $files = $this->imageFile;
            if (!is_array($files)) $files = [$files];
            
            foreach ($files AS $f) {
                $ext = $f->extension;
    
                $name = uniqid() . '.' . $ext;
                $path = \Yii::getAlias('@app') . '/' . self::UPLOAD_DIR . '/' . $type;
                $result_path = "{$path}/{$name}";
    
                $this->imageName[] = $name;
                $this->imagePath = '/' . self::VIEW_DIR . '/' . $type . '/' . $name;

                // сохраняем основной файл
                $f->saveAs($result_path);

                // пути для миниатюр
                $path_img_lg = str_replace('.' . $ext, '_lg.' . $ext, $result_path);
                $path_img_md = str_replace('.' . $ext, '_md.' . $ext, $result_path);
                $path_img_sm = str_replace('.' . $ext, '_sm.' . $ext, $result_path);
                $path_img_xs = str_replace('.' . $ext, '_xs.' . $ext, $result_path);

                // создаем миниатюры
                Image::open($result_path)->cropResize(800, 800)->save($path_img_lg, 'guess', self::SAVE_QUALITY);
                Image::open($result_path)->cropResize(400, 400)->save($path_img_md, 'guess', self::SAVE_QUALITY);
                Image::open($result_path)->cropResize(200, 200)->save($path_img_sm, 'guess', self::SAVE_QUALITY);
                Image::open($result_path)->cropResize(100, 100)->save($path_img_xs, 'guess', self::SAVE_QUALITY);
                // уменьшаем оригинал
                Image::open($result_path)->cropResize(1200, 1200)->save($result_path, 'guess', self::SAVE_QUALITY);

                // подпись-маркер
                $this->setTextMark(self::MARK_TEXT, $result_path);
                $this->setTextMark(self::MARK_TEXT, $path_img_lg);
                $this->setTextMark(self::MARK_TEXT, $path_img_md);
            }

            return true;
        } else {
            return false;
        }
    }

    /**
     * установка текста-маркера с обводкой
     * сначала рисуем обводку по четырем сторонам, а потом основной текст
     *
     * @param $text
     * @param $img_path
     */
    public function setTextMark($text, $img_path) {
        $img = ImageManager::make($img_path);
        
        $img_width = $img->getWidth();
        $img_height = $img->getHeight();
        
        // определяем размер шрифта и отступы в зависимости от ширины фото
        // отрицательные числа для вертикального текста
        switch (true) {
            case ($img_width >= 600): $position = 20; $size = 24; break;
            case ($img_width >= 300): $position = 15; $size = 20; break;
            case ($img_height >= 600): $position = 30; $size = -24; break;
            case ($img_height >= 300): $position = 30; $size = -20; break;
            default: return;
        }
        
        $img->text($text, $img->getWidth() - $position, $img->getHeight() - $position + 1, function(Font $font) use ($size) {
            self::setFont($font, $size, self::FONT_BORDER_COLOR); // обводка снизу
        })->text($text, $img->getWidth() - $position, $img->getHeight() - $position - 1, function(Font $font) use ($size) {
            self::setFont($font, $size, self::FONT_BORDER_COLOR); // обводка сверху
        })->text($text, $img->getWidth() - $position - 1, $img->getHeight() - $position, function(Font $font) use ($size) {
            self::setFont($font, $size, self::FONT_BORDER_COLOR); // обводка слева
        })->text($text, $img->getWidth() - $position + 1, $img->getHeight() - $position, function(Font $font) use ($size) {
            self::setFont($font, $size, self::FONT_BORDER_COLOR); // обводка справа
        })->text($text, $img->getWidth() - $position, $img->getHeight() - $position, function(Font $font) use ($size) {
            self::setFont($font, $size, self::FONT_COLOR); // основной текст
        })->save();
        unset($img);
    }

    /**
     * установка настроек шрифта
     * @param Font $font
     * @param $font_size
     * @param $font_color
     */
    protected static function setFont(Font &$font, $font_size, $font_color) {
        $color = explode(',', $font_color); // '50,50,50,0.5' => array(50,50,50,0.5)
        if (count($color) == 1) $color = $font_color;

        $font->file(\Yii::getAlias('@app') . '/' . self::FONT_ALIAS);
        if ($font_size < 0) $font->angle(-90);
        $font->size(abs($font_size));
        $font->color($color);
        $font->align('right');
        $font->valign('bottom');
    }

    public function getImagePath($full_path = false, $suffix = null) {

        $img_path = $this->imagePath;
    
        if ($suffix) $img_path = preg_replace('/(\.[\w]+)$/i', $suffix . '$1', $img_path);
        if ($full_path) $img_path = Yii::$app->request->hostInfo . $img_path;

        return $img_path;
    }

    public static function getSrc($img_name, $type = self::TYPE_CAMP, $suffix = null) {

        if ($suffix) $img_name = preg_replace('/(\.[\w]+)$/i', $suffix . '$1', $img_name);
        $src = [self::VIEW_DIR, $type, $img_name];

        return '/' . implode('/', $src);
    }
}